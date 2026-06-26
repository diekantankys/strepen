import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../l10n/app_localizations.dart';
import '../models/post.dart';
import '../models/post_comment.dart';
import '../services/post_comment_service.dart';
import 'home_screen_posts_tab.dart';

class PostDetailScreen extends StatefulWidget {
  final Post post;

  const PostDetailScreen({Key? key, required this.post}) : super(key: key);

  @override
  State createState() {
    return _PostDetailScreenState(post: post);
  }
}

class _PostDetailScreenState extends State {
  final Post post;

  _PostDetailScreenState({required this.post});

  List<PostComment> _comments = [];
  bool _isLoading = true;
  bool _hasError = false;
  bool _isSubmitting = false;
  int? _replyingToId;
  String? _replyingToName;
  final TextEditingController _commentController = TextEditingController();
  final TextEditingController _replyController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadComments();
  }

  @override
  void dispose() {
    _commentController.dispose();
    _replyController.dispose();
    super.dispose();
  }

  Future<void> _loadComments() async {
    try {
      final comments = await PostCommentsService.getInstance().comments(
        postId: post.id,
      );
      if (mounted) {
        setState(() {
          _comments = comments;
          _isLoading = false;
          _hasError = false;
        });
      }
    } catch (exception, stacktrace) {
      print(exception);
      print(stacktrace);
      if (mounted) {
        setState(() {
          _hasError = true;
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _submitComment() async {
    final body = _commentController.text.trim();
    if (body.isEmpty || _isSubmitting) return;
    setState(() => _isSubmitting = true);
    try {
      await PostCommentsService.getInstance().createComment(
        postId: post.id,
        body: body,
      );
      _commentController.clear();
      await _loadComments();
    } catch (exception, stacktrace) {
      print(exception);
      print(stacktrace);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  Future<void> _submitReply() async {
    if (_replyingToId == null || _isSubmitting) return;
    final body = _replyController.text.trim();
    if (body.isEmpty) return;
    setState(() => _isSubmitting = true);
    try {
      await PostCommentsService.getInstance().createComment(
        postId: post.id,
        body: body,
        parentId: _replyingToId,
      );
      _replyController.clear();
      if (mounted) {
        setState(() {
          _replyingToId = null;
          _replyingToName = null;
        });
      }
      await _loadComments();
    } catch (exception, stacktrace) {
      print(exception);
      print(stacktrace);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(title: Text(post.title)),
      body: RefreshIndicator(
        onRefresh: _loadComments,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            PostItem(post: post, isDetail: true),
            const SizedBox(height: 16),
            Text(
              lang.post_detail_comments_title,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            if (_isLoading)
              const Center(child: CircularProgressIndicator())
            else if (_hasError)
              Text(lang.post_detail_comments_error)
            else if (_comments.isEmpty)
              Text(
                lang.post_detail_comments_empty,
                style: const TextStyle(fontStyle: FontStyle.italic),
              )
            else
              ..._comments.map(
                (c) => CommentItem(
                  key: ValueKey(c.id),
                  comment: c,
                  postId: post.id,
                  depth: 0,
                  replyingToId: _replyingToId,
                  replyingToName: _replyingToName,
                  replyController: _replyController,
                  isSubmittingReply: _isSubmitting,
                  onReply: (id, name) {
                    setState(() {
                      _replyingToId = id;
                      _replyingToName = name;
                      _replyController.clear();
                    });
                  },
                  onSubmitReply: _submitReply,
                  onCancelReply: () => setState(() {
                    _replyingToId = null;
                    _replyingToName = null;
                  }),
                  onChanged: _loadComments,
                ),
              ),
            const SizedBox(height: 24),
            TextField(
              controller: _commentController,
              decoration: InputDecoration(
                hintText: lang.post_detail_comment_placeholder,
                border: const OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _submitComment,
                child: Text(lang.post_detail_comment_submit),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}

class CommentItem extends StatefulWidget {
  final PostComment comment;
  final int postId;
  final int depth;
  final int? replyingToId;
  final String? replyingToName;
  final TextEditingController replyController;
  final bool isSubmittingReply;
  final void Function(int id, String name) onReply;
  final Future<void> Function() onSubmitReply;
  final VoidCallback onCancelReply;
  final VoidCallback onChanged;

  const CommentItem({
    Key? key,
    required this.comment,
    required this.postId,
    required this.depth,
    required this.replyingToId,
    required this.replyingToName,
    required this.replyController,
    required this.isSubmittingReply,
    required this.onReply,
    required this.onSubmitReply,
    required this.onCancelReply,
    required this.onChanged,
  }) : super(key: key);

  @override
  State<CommentItem> createState() {
    return _CommentItemState();
  }
}

class _CommentItemState extends State<CommentItem> {
  late PostComment comment;
  bool _isReacting = false;

  @override
  void initState() {
    super.initState();
    comment = widget.comment;
  }

  @override
  void didUpdateWidget(covariant CommentItem oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!_isReacting) comment = widget.comment;
  }

  Future<void> _react(Future<void> Function() reaction) async {
    if (_isReacting) return;
    setState(() => _isReacting = true);
    try {
      await reaction();
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
    return Padding(
      padding: EdgeInsets.only(left: widget.depth * 16.0, bottom: 8),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    backgroundImage: comment.user != null
                        ? CachedNetworkImageProvider(comment.user!.avatar)
                        : null,
                    radius: 16,
                    child: comment.user == null
                        ? const Icon(Icons.person, size: 16)
                        : null,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          comment.user?.name ?? '',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        Text(
                          DateFormat(
                            'yyyy-MM-dd HH:mm',
                          ).format(comment.createdAt),
                          style: const TextStyle(
                            color: Colors.grey,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerLeft,
                child: _CommentReactionButtons(
                  comment: comment,
                  isReacting: _isReacting,
                  onLike: () => _react(() => comment.like(widget.postId)),
                  onDislike: () => _react(() => comment.dislike(widget.postId)),
                ),
              ),
              const SizedBox(height: 8),
              Text(comment.body),
              const SizedBox(height: 8),
              if (widget.depth < 3)
                TextButton(
                  onPressed: () =>
                      widget.onReply(comment.id, comment.user?.name ?? ''),
                  child: Text(lang.post_detail_comment_reply),
                ),
              if (widget.replyingToId == comment.id) ...[
                Text(
                  lang.post_detail_replying_to(widget.replyingToName ?? ''),
                  style: const TextStyle(
                    fontStyle: FontStyle.italic,
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: widget.replyController,
                  decoration: InputDecoration(
                    hintText: lang.post_detail_comment_placeholder,
                    border: const OutlineInputBorder(),
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    ElevatedButton(
                      onPressed: widget.isSubmittingReply
                          ? null
                          : widget.onSubmitReply,
                      child: Text(lang.post_detail_comment_submit),
                    ),
                    const SizedBox(width: 8),
                    TextButton(
                      onPressed: widget.onCancelReply,
                      child: Text(lang.post_detail_comment_cancel),
                    ),
                  ],
                ),
              ],
              ...comment.replies.map(
                (r) => CommentItem(
                  key: ValueKey(r.id),
                  comment: r,
                  postId: widget.postId,
                  depth: widget.depth + 1,
                  replyingToId: widget.replyingToId,
                  replyingToName: widget.replyingToName,
                  replyController: widget.replyController,
                  isSubmittingReply: widget.isSubmittingReply,
                  onReply: widget.onReply,
                  onSubmitReply: widget.onSubmitReply,
                  onCancelReply: widget.onCancelReply,
                  onChanged: widget.onChanged,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CommentReactionButtons extends StatelessWidget {
  final PostComment comment;
  final bool isReacting;
  final VoidCallback onLike;
  final VoidCallback onDislike;

  const _CommentReactionButtons({
    required this.comment,
    required this.isReacting,
    required this.onLike,
    required this.onDislike,
  });

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return Wrap(
      spacing: 8,
      children: [
        comment.userLiked
            ? ElevatedButton.icon(
                onPressed: isReacting ? null : onLike,
                style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                icon: const Icon(Icons.thumb_up_alt, color: Colors.white),
                label: Text(
                  comment.likes > 0
                      ? comment.likes.toString()
                      : lang.post_detail_comment_like,
                  style: const TextStyle(color: Colors.white),
                ),
              )
            : OutlinedButton.icon(
                onPressed: isReacting ? null : onLike,
                icon: const Icon(Icons.thumb_up_alt_outlined),
                label: Text(
                  comment.likes > 0
                      ? comment.likes.toString()
                      : lang.post_detail_comment_like,
                ),
              ),
        comment.userDisliked
            ? ElevatedButton.icon(
                onPressed: isReacting ? null : onDislike,
                style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                icon: const Icon(Icons.thumb_down_alt, color: Colors.white),
                label: Text(
                  comment.dislikes > 0
                      ? comment.dislikes.toString()
                      : lang.post_detail_comment_dislike,
                  style: const TextStyle(color: Colors.white),
                ),
              )
            : OutlinedButton.icon(
                onPressed: isReacting ? null : onDislike,
                icon: const Icon(Icons.thumb_down_alt_outlined),
                label: Text(
                  comment.dislikes > 0
                      ? comment.dislikes.toString()
                      : lang.post_detail_comment_dislike,
                ),
              ),
      ],
    );
  }
}
