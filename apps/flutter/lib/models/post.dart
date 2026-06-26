import '../services/post_service.dart';
import 'user.dart';

class Post {
  final int id;
  final User? user;
  final String title;
  final String? image;
  final String body;
  int likes;
  bool userLiked;
  int dislikes;
  bool userDisliked;
  final DateTime createdAt;

  Post({
    required this.id,
    required this.user,
    required this.title,
    required this.image,
    required this.body,
    required this.likes,
    required this.userLiked,
    required this.dislikes,
    required this.userDisliked,
    required this.createdAt,
  });

  factory Post.fromJson(Map<String, dynamic> json) {
    return Post(
      id: json['id'],
      user: json['user'] != null ? User.fromJson(json['user']) : null,
      title: json['title'],
      image: json['image'],
      body: json['body'],
      likes: json['likes'],
      userLiked: json['user_liked'],
      dislikes: json['dislikes'],
      userDisliked: json['user_disliked'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Future<void> like() async {
    final updatedPost = await PostsService.getInstance().like(postId: id);
    likes = updatedPost.likes;
    userLiked = updatedPost.userLiked;
    dislikes = updatedPost.dislikes;
    userDisliked = updatedPost.userDisliked;
  }

  Future<void> dislike() async {
    final updatedPost = await PostsService.getInstance().dislike(postId: id);
    likes = updatedPost.likes;
    userLiked = updatedPost.userLiked;
    dislikes = updatedPost.dislikes;
    userDisliked = updatedPost.userDisliked;
  }
}
