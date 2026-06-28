import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../l10n/app_localizations.dart';
import '../models/post.dart';
import '../models/post_comment.dart';
import '../services/auth_service.dart';
import '../services/post_comment_service.dart';
import '../services/storage_service.dart';
import 'home_screen_posts_tab.dart';

class PostDetailScreen extends StatefulWidget {
  final Post post;

  const PostDetailScreen({super.key, required this.post});

  @override
  State createState() {
    return _PostDetailScreenState();
  }
}

class _PostDetailScreenState extends State<PostDetailScreen> {
  List<PostComment> _comments = [];
  bool _isLoading = true;
  bool _hasError = false;
  bool _isSubmitting = false;
  int? _currentUserId;
  bool _isAdmin = false;
  int? _replyingToId;
  String? _replyingToName;
  final TextEditingController _commentController = TextEditingController();
  final TextEditingController _replyController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadCurrentUser();
    _loadComments();
  }

  Future<void> _loadCurrentUser() async {
    final storage = await StorageService.getInstance();
    final user = await AuthService.getInstance().user();
    if (mounted) {
      setState(() {
        _currentUserId = storage.userId;
        _isAdmin = user?.role == 'admin';
      });
    }
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
        postId: widget.post.id,
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
        postId: widget.post.id,
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
        postId: widget.post.id,
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

  Future<void> _deleteComment(int commentId) async {
    if (_isSubmitting) return;
    setState(() => _isSubmitting = true);
    try {
      await PostCommentsService.getInstance().deleteComment(
        postId: widget.post.id,
        commentId: commentId,
      );
      if (_replyingToId == commentId && mounted) {
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

  Future<void> _requestDeleteComment(int commentId) async {
    final lang = AppLocalizations.of(context)!;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(MaterialLocalizations.of(context).deleteButtonTooltip),
        content: Text(lang.post_detail_comment_confirm_delete),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(MaterialLocalizations.of(context).cancelButtonLabel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(MaterialLocalizations.of(context).deleteButtonTooltip),
          ),
        ],
      ),
    );
    if (confirmed == true) await _deleteComment(commentId);
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(title: Text(widget.post.title)),
      body: RefreshIndicator(
        onRefresh: _loadComments,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            PostItem(post: widget.post, isDetail: true),
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
                  postId: widget.post.id,
                  depth: 0,
                  replyingToId: _replyingToId,
                  replyingToName: _replyingToName,
                  replyController: _replyController,
                  isSubmittingReply: _isSubmitting,
                  currentUserId: _currentUserId,
                  isAdmin: _isAdmin,
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
                  onDelete: _requestDeleteComment,
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
  final int? currentUserId;
  final bool isAdmin;
  final void Function(int id, String name) onReply;
  final Future<void> Function() onSubmitReply;
  final VoidCallback onCancelReply;
  final Future<void> Function(int commentId) onDelete;
  final VoidCallback onChanged;

  const CommentItem({
    super.key,
    required this.comment,
    required this.postId,
    required this.depth,
    required this.replyingToId,
    required this.replyingToName,
    required this.replyController,
    required this.isSubmittingReply,
    required this.currentUserId,
    required this.isAdmin,
    required this.onReply,
    required this.onSubmitReply,
    required this.onCancelReply,
    required this.onDelete,
    required this.onChanged,
  });

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
    final canDelete =
        widget.isAdmin ||
        (comment.user?.id == widget.currentUserId && comment.replies.isEmpty);
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
                  canReply: widget.depth < 3,
                  canDelete: canDelete,
                  onDelete: () => widget.onDelete(comment.id),
                  onReply: () =>
                      widget.onReply(comment.id, comment.user?.name ?? ''),
                  onLike: () => _react(() => comment.like(widget.postId)),
                  onDislike: () => _react(() => comment.dislike(widget.postId)),
                ),
              ),
              const SizedBox(height: 8),
              Text(comment.body),
              const SizedBox(height: 8),
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
                  currentUserId: widget.currentUserId,
                  isAdmin: widget.isAdmin,
                  onReply: widget.onReply,
                  onSubmitReply: widget.onSubmitReply,
                  onCancelReply: widget.onCancelReply,
                  onDelete: widget.onDelete,
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
  final bool canReply;
  final bool canDelete;
  final VoidCallback onReply;
  final VoidCallback onDelete;
  final VoidCallback onLike;
  final VoidCallback onDislike;

  const _CommentReactionButtons({
    required this.comment,
    required this.isReacting,
    required this.canReply,
    required this.canDelete,
    required this.onReply,
    required this.onDelete,
    required this.onLike,
    required this.onDislike,
  });

  Widget _iconButton({
    required IconData icon,
    required String tooltip,
    required VoidCallback? onPressed,
    Color? backgroundColor,
    Color? foregroundColor,
  }) {
    final iconWidget = Icon(icon, size: 18);
    return Tooltip(
      message: tooltip,
      child: backgroundColor != null
          ? ElevatedButton(
              onPressed: onPressed,
              style: ElevatedButton.styleFrom(
                backgroundColor: backgroundColor,
                foregroundColor: foregroundColor,
                fixedSize: const Size(36, 36),
                padding: EdgeInsets.zero,
                visualDensity: VisualDensity.compact,
              ),
              child: iconWidget,
            )
          : OutlinedButton(
              onPressed: onPressed,
              style: OutlinedButton.styleFrom(
                foregroundColor: foregroundColor,
                fixedSize: const Size(36, 36),
                padding: EdgeInsets.zero,
                visualDensity: VisualDensity.compact,
              ),
              child: iconWidget,
            ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return LayoutBuilder(
      builder: (context, constraints) {
        final showLabels = constraints.maxWidth >= 480;
        return Wrap(
          spacing: 6,
          children: [
            if (canReply)
              showLabels
                  ? OutlinedButton.icon(
                      onPressed: onReply,
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size(0, 36),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 8,
                        ),
                        visualDensity: VisualDensity.compact,
                      ),
                      icon: const Icon(Icons.reply_outlined, size: 18),
                      label: Text(
                        lang.post_detail_comment_reply,
                        style: const TextStyle(fontSize: 12),
                      ),
                    )
                  : _iconButton(
                      icon: Icons.reply_outlined,
                      tooltip: lang.post_detail_comment_reply,
                      onPressed: onReply,
                    ),
            comment.userLiked
                ? (showLabels || comment.likes > 0)
                      ? ElevatedButton.icon(
                          onPressed: isReacting ? null : onLike,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                            minimumSize: const Size(0, 36),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 8,
                            ),
                            visualDensity: VisualDensity.compact,
                          ),
                          icon: const Icon(
                            Icons.thumb_up_alt,
                            color: Colors.white,
                            size: 18,
                          ),
                          label: Text(
                            comment.likes > 0
                                ? comment.likes.toString()
                                : lang.post_detail_comment_like,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                            ),
                          ),
                        )
                      : _iconButton(
                          icon: Icons.thumb_up_alt,
                          tooltip: lang.post_detail_comment_like,
                          onPressed: isReacting ? null : onLike,
                          backgroundColor: Colors.green,
                          foregroundColor: Colors.white,
                        )
                : (showLabels || comment.likes > 0)
                ? OutlinedButton.icon(
                    onPressed: isReacting ? null : onLike,
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(0, 36),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 8,
                      ),
                      visualDensity: VisualDensity.compact,
                    ),
                    icon: const Icon(Icons.thumb_up_alt_outlined, size: 18),
                    label: Text(
                      comment.likes > 0
                          ? comment.likes.toString()
                          : lang.post_detail_comment_like,
                      style: const TextStyle(fontSize: 12),
                    ),
                  )
                : _iconButton(
                    icon: Icons.thumb_up_alt_outlined,
                    tooltip: lang.post_detail_comment_like,
                    onPressed: isReacting ? null : onLike,
                  ),
            comment.userDisliked
                ? (showLabels || comment.dislikes > 0)
                      ? ElevatedButton.icon(
                          onPressed: isReacting ? null : onDislike,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.red,
                            minimumSize: const Size(0, 36),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 8,
                            ),
                            visualDensity: VisualDensity.compact,
                          ),
                          icon: const Icon(
                            Icons.thumb_down_alt,
                            color: Colors.white,
                            size: 18,
                          ),
                          label: Text(
                            comment.dislikes > 0
                                ? comment.dislikes.toString()
                                : lang.post_detail_comment_dislike,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                            ),
                          ),
                        )
                      : _iconButton(
                          icon: Icons.thumb_down_alt,
                          tooltip: lang.post_detail_comment_dislike,
                          onPressed: isReacting ? null : onDislike,
                          backgroundColor: Colors.red,
                          foregroundColor: Colors.white,
                        )
                : (showLabels || comment.dislikes > 0)
                ? OutlinedButton.icon(
                    onPressed: isReacting ? null : onDislike,
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(0, 36),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 8,
                      ),
                      visualDensity: VisualDensity.compact,
                    ),
                    icon: const Icon(Icons.thumb_down_alt_outlined, size: 18),
                    label: Text(
                      comment.dislikes > 0
                          ? comment.dislikes.toString()
                          : lang.post_detail_comment_dislike,
                      style: const TextStyle(fontSize: 12),
                    ),
                  )
                : _iconButton(
                    icon: Icons.thumb_down_alt_outlined,
                    tooltip: lang.post_detail_comment_dislike,
                    onPressed: isReacting ? null : onDislike,
                  ),
            if (canDelete)
              showLabels
                  ? OutlinedButton.icon(
                      onPressed: isReacting ? null : onDelete,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.red,
                        minimumSize: const Size(0, 36),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 8,
                        ),
                        visualDensity: VisualDensity.compact,
                      ),
                      icon: const Icon(Icons.delete_outline, size: 18),
                      label: Text(
                        MaterialLocalizations.of(context).deleteButtonTooltip,
                        style: const TextStyle(fontSize: 12),
                      ),
                    )
                  : _iconButton(
                      icon: Icons.delete_outline,
                      tooltip: MaterialLocalizations.of(
                        context,
                      ).deleteButtonTooltip,
                      onPressed: isReacting ? null : onDelete,
                      foregroundColor: Colors.red,
                    ),
          ],
        );
      },
    );
  }
}
