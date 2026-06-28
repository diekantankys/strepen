import 'post.dart';

enum NotificationType { newDeposit, newPost, lowBalance, newTransaction }

NotificationType? notificationTypeFromString(String type) {
  if (type == 'new_deposit') return NotificationType.newDeposit;
  if (type == 'new_post') return NotificationType.newPost;
  if (type == 'low_balance') return NotificationType.lowBalance;
  if (type == 'new_transaction') return NotificationType.newTransaction;
  return null;
}

class NotificationData {
  final String id;
  final NotificationType type;
  final Map<String, dynamic> data;
  final Post? post;
  final DateTime? readAt;
  final DateTime createdAt;

  const NotificationData({
    required this.id,
    required this.type,
    required this.data,
    required this.post,
    required this.readAt,
    required this.createdAt,
  });

  factory NotificationData.fromJson(Map<String, dynamic> json) {
    return NotificationData(
      id: json['id'],
      type: notificationTypeFromString(json['type'])!,
      data: json['data'],
      post: json['post'] != null ? Post.fromJson(json['post']) : null,
      readAt: json['read_at'] != null ? DateTime.parse(json['read_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}
