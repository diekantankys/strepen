import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:html/dom.dart' as dom;
import 'package:intl/intl.dart';
import '../l10n/app_localizations.dart';
import '../models/post.dart';
import '../services/post_service.dart';
import 'post_detail_screen.dart';

class HomeScreenPostsTab extends StatefulWidget {
  const HomeScreenPostsTab({super.key});

  @override
  State createState() {
    return _HomeScreenPostsTabState();
  }
}

class _HomeScreenPostsTabState extends State {
  final ScrollController _scrollController = ScrollController();

  List<Post> _posts = [];
  final List<int> _loadedPages = [];
  int _page = 1;
  bool _isLoading = true;
  bool _hasError = false;
  bool _isDone = false;

  @override
  void initState() {
    super.initState();
    loadNextPage();
    _scrollController.addListener(() {
      if (!_isLoading &&
          _scrollController.position.pixels >
              _scrollController.position.maxScrollExtent * 0.9) {
        loadNextPage();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> loadNextPage() async {
    if (_isDone) return;

    _isLoading = true;
    List<Post> newPosts;
    try {
      newPosts = await PostsService.getInstance().posts(
        page: _page,
        forceReload: _loadedPages.contains(_page),
      );
      if (!_loadedPages.contains(_page)) {
        _loadedPages.add(_page);
      }
    } catch (exception, stacktrace) {
      print(exception);
      print(stacktrace);

      _isLoading = false;
      if (mounted) {
        setState(() => _hasError = true);
      }
      return;
    }
    if (newPosts.isNotEmpty) {
      _posts.addAll(newPosts);
      _page++;
    } else {
      _isDone = true;
    }

    _isLoading = false;
    if (newPosts.isNotEmpty && mounted) {
      setState(() => _posts = _posts);
    }
  }

  Future<void> _refresh() async {
    PostsService.getInstance().clearCache();
    setState(() {
      _posts = [];
      _loadedPages.clear();
      _page = 1;
      _isDone = false;
      _hasError = false;
    });
    await loadNextPage();
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return RefreshIndicator(
      onRefresh: _refresh,
      child: _posts.isNotEmpty
          ? ListView.builder(
              controller: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              itemCount: _posts.length,
              itemBuilder: (context, index) => PostItem(post: _posts[index]),
            )
          : ListView(
              controller: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              children: [
                SizedBox(
                  height: MediaQuery.sizeOf(context).height * 0.65,
                  child: Center(
                    child: _hasError
                        ? Text(lang.home_posts_error)
                        : (_isLoading
                              ? const CircularProgressIndicator()
                              : Text(lang.home_posts_empty)),
                  ),
                ),
              ],
            ),
    );
  }
}

class PostItem extends StatefulWidget {
  final Post post;
  final bool isDetail;

  const PostItem({Key? key, required this.post, this.isDetail = false})
    : super(key: key);

  @override
  State createState() {
    return _PostItemState(post: post);
  }
}

class _PostItemState extends State<PostItem> {
  final Post post;

  _PostItemState({required this.post});

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    final isMobile =
        defaultTargetPlatform == TargetPlatform.iOS ||
        defaultTargetPlatform == TargetPlatform.android;
    return Center(
      child: Container(
        constraints: BoxConstraints(
          maxWidth: !isMobile ? 560 : double.infinity,
        ),
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: InkWell(
          onTap: widget.isDetail
              ? null
              : () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => PostDetailScreen(post: post),
                  ),
                ),
          child: Card(
            clipBehavior: Clip.antiAliasWithSaveLayer,
            child: Column(
              children: [
                if (post.image != null) ...[
                  AspectRatio(
                    aspectRatio: 16 / 9,
                    child: Container(
                      decoration: BoxDecoration(
                        image: DecorationImage(
                          fit: BoxFit.cover,
                          image: CachedNetworkImageProvider(post.image!),
                        ),
                      ),
                    ),
                  ),
                ],
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        post.title,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Align(
                        alignment: Alignment.centerLeft,
                        child: _PostVoteButtons(
                          post: post,
                          onChanged: () => setState(() {}),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        width: double.infinity,
                        child: Text(
                          lang.home_posts_written_by(
                            post.user!.name,
                            DateFormat(
                              'yyyy-MM-dd kk:mm',
                            ).format(post.createdAt),
                          ),
                          style: const TextStyle(color: Colors.grey),
                        ),
                      ),
                      Html(
                        data: post.body,
                        style: {
                          'body': Style(
                            margin: Margins.zero,
                            padding: HtmlPaddings.zero,
                          ),
                        },
                        onLinkTap:
                            (
                              String? url,
                              Map<String, String> attributes,
                              dom.Element? element,
                            ) async {
                              if (url == null) return;
                              Uri uri = Uri.parse(url);
                              if (await canLaunchUrl(uri)) await launchUrl(uri);
                            },
                      ),
                      Offstage(
                        child: Align(
                          alignment: Alignment.centerRight,
                          child: Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            alignment: WrapAlignment.end,
                            children: [
                              // Like button
                              post.userLiked
                                  ? ElevatedButton.icon(
                                      onPressed: () async {
                                        await post.like();
                                        setState(() {});
                                      },
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: Colors.green,
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(
                                            8,
                                          ),
                                        ),
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 24,
                                          vertical: 12,
                                        ),
                                      ),
                                      icon: const Icon(
                                        Icons.thumb_up_alt,
                                        color: Colors.white,
                                      ),
                                      label: Text(
                                        post.likes > 0
                                            ? post.likes.toString()
                                            : lang.home_posts_like,
                                        style: const TextStyle(
                                          color: Colors.white,
                                        ),
                                      ),
                                    )
                                  : OutlinedButton.icon(
                                      onPressed: () async {
                                        await post.like();
                                        setState(() {});
                                      },
                                      style: OutlinedButton.styleFrom(
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(
                                            8,
                                          ),
                                        ),
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 24,
                                          vertical: 12,
                                        ),
                                      ),
                                      icon: const Icon(
                                        Icons.thumb_up_alt_outlined,
                                      ),
                                      label: Text(
                                        post.likes > 0
                                            ? post.likes.toString()
                                            : lang.home_posts_like,
                                      ),
                                    ),

                              // Dislike button
                              post.userDisliked
                                  ? ElevatedButton.icon(
                                      onPressed: () async {
                                        await post.dislike();
                                        setState(() {});
                                      },
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: Colors.red,
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(
                                            8,
                                          ),
                                        ),
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 24,
                                          vertical: 12,
                                        ),
                                      ),
                                      icon: const Icon(
                                        Icons.thumb_down_alt,
                                        color: Colors.white,
                                      ),
                                      label: Text(
                                        post.dislikes > 0
                                            ? post.dislikes.toString()
                                            : lang.home_posts_dislike,
                                        style: const TextStyle(
                                          color: Colors.white,
                                        ),
                                      ),
                                    )
                                  : OutlinedButton.icon(
                                      onPressed: () async {
                                        await post.dislike();
                                        setState(() {});
                                      },
                                      style: OutlinedButton.styleFrom(
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(
                                            8,
                                          ),
                                        ),
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 24,
                                          vertical: 12,
                                        ),
                                      ),
                                      icon: const Icon(
                                        Icons.thumb_down_alt_outlined,
                                      ),
                                      label: Text(
                                        post.dislikes > 0
                                            ? post.dislikes.toString()
                                            : lang.home_posts_dislike,
                                      ),
                                    ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _PostVoteButtons extends StatefulWidget {
  final Post post;
  final VoidCallback onChanged;

  const _PostVoteButtons({required this.post, required this.onChanged});

  @override
  State<_PostVoteButtons> createState() => _PostVoteButtonsState();
}

class _PostVoteButtonsState extends State<_PostVoteButtons> {
  bool _isReacting = false;

  Future<void> _react(Future<void> Function() reaction) async {
    if (_isReacting) return;
    setState(() => _isReacting = true);
    try {
      await reaction();
      widget.onChanged();
    } catch (exception, stacktrace) {
      print(exception);
      print(stacktrace);
    } finally {
      if (mounted) setState(() => _isReacting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;

    return Wrap(
      spacing: 8,
      children: [
        widget.post.userLiked
            ? ElevatedButton.icon(
                onPressed: _isReacting
                    ? null
                    : () => _react(() => widget.post.like()),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                icon: const Icon(Icons.thumb_up_alt, color: Colors.white),
                label: Text(
                  widget.post.likes > 0
                      ? widget.post.likes.toString()
                      : lang.home_posts_like,
                  style: const TextStyle(color: Colors.white),
                ),
              )
            : OutlinedButton.icon(
                onPressed: _isReacting
                    ? null
                    : () => _react(() => widget.post.like()),
                style: OutlinedButton.styleFrom(
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                icon: const Icon(Icons.thumb_up_alt_outlined),
                label: Text(
                  widget.post.likes > 0
                      ? widget.post.likes.toString()
                      : lang.home_posts_like,
                ),
              ),
        widget.post.userDisliked
            ? ElevatedButton.icon(
                onPressed: _isReacting
                    ? null
                    : () => _react(() => widget.post.dislike()),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                icon: const Icon(Icons.thumb_down_alt, color: Colors.white),
                label: Text(
                  widget.post.dislikes > 0
                      ? widget.post.dislikes.toString()
                      : lang.home_posts_dislike,
                  style: const TextStyle(color: Colors.white),
                ),
              )
            : OutlinedButton.icon(
                onPressed: _isReacting
                    ? null
                    : () => _react(() => widget.post.dislike()),
                style: OutlinedButton.styleFrom(
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                icon: const Icon(Icons.thumb_down_alt_outlined),
                label: Text(
                  widget.post.dislikes > 0
                      ? widget.post.dislikes.toString()
                      : lang.home_posts_dislike,
                ),
              ),
      ],
    );
  }
}
