import '../services/post_comment_service.dart';
import 'user.dart';

class PostComment {
  final int id;
  final int? parentId;
  final User? user;
  final String body;
  int likes;
  bool userLiked;
  int dislikes;
  bool userDisliked;
  final DateTime createdAt;
  final List<PostComment> replies;

  PostComment({
    required this.id,
    required this.parentId,
    required this.user,
    required this.body,
    required this.likes,
    required this.userLiked,
    required this.dislikes,
    required this.userDisliked,
    required this.createdAt,
    required this.replies,
  });

  factory PostComment.fromJson(Map<String, dynamic> json) {
    return PostComment(
      id: _intValue(json['id'], 'id'),
      parentId: _intOrNull(json['parent_id']),
      user: json['user'] != null ? User.fromJson(json['user']) : null,
      body: json['body'],
      likes: _intValue(json['likes'], 'likes'),
      userLiked: _boolValue(json['user_liked']),
      dislikes: _intValue(json['dislikes'], 'dislikes'),
      userDisliked: _boolValue(json['user_disliked']),
      createdAt: DateTime.parse(json['created_at']),
      replies: _repliesFromJson(json['replies']),
    );
  }

  static int _intValue(dynamic value, String field) {
    if (value is int) return value;
    if (value is String) {
      final parsed = int.tryParse(value);
      if (parsed != null) return parsed;
    }
    throw FormatException('Invalid comment $field.');
  }

  static int? _intOrNull(dynamic value) {
    if (value == null || value == '') return null;
    return _intValue(value, 'parent_id');
  }

  static bool _boolValue(dynamic value) {
    if (value is bool) return value;
    if (value is int) return value != 0;
    if (value is String) return value == '1' || value.toLowerCase() == 'true';
    throw const FormatException('Invalid comment vote state.');
  }

  static List<PostComment> _repliesFromJson(dynamic value) {
    // Nested Laravel resource collections may be represented either as a list
    // or as a {data: [...]} wrapper, depending on the resource configuration.
    final replies = value is Map<String, dynamic> ? value['data'] : value;
    if (replies == null) return [];
    if (replies is! List) {
      throw const FormatException('Invalid comment replies.');
    }

    return replies.map<PostComment>((reply) {
      if (reply is! Map<String, dynamic>) {
        throw const FormatException('Invalid comment reply.');
      }
      return PostComment.fromJson(reply);
    }).toList();
  }

  Future<void> like(int postId) async {
    final updated = await PostCommentsService.getInstance().likeComment(
      postId: postId,
      commentId: id,
    );
    likes = updated.likes;
    userLiked = updated.userLiked;
    dislikes = updated.dislikes;
    userDisliked = updated.userDisliked;
  }

  Future<void> dislike(int postId) async {
    final updated = await PostCommentsService.getInstance().dislikeComment(
      postId: postId,
      commentId: id,
    );
    likes = updated.likes;
    userLiked = updated.userLiked;
    dislikes = updated.dislikes;
    userDisliked = updated.userDisliked;
  }
}
