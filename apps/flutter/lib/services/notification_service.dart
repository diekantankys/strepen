import 'package:flutter/material.dart';
import '../screens/post_detail_screen.dart';
import 'auth_service.dart';
import 'post_service.dart';

class NotificationService {
  static NotificationService? _instance;

  final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  // Incremented whenever a notification is read via push tap so the
  // NotificationsButton can listen and force-reload its FutureBuilder.
  final ValueNotifier<int> unreadChanged = ValueNotifier<int>(0);

  // Stored when the app is cold-started via a notification tap; consumed by HomeScreen.
  Map<String, dynamic>? pendingData;

  static NotificationService getInstance() {
    _instance ??= NotificationService();
    return _instance!;
  }

  Future<void> handleData(Map<String, dynamic> data) async {
    final type = data['type'] as String?;
    final notificationId = data['notification_id'] as String?;

    if (notificationId != null) {
      try {
        await AuthService.getInstance().readNotification(
          notificationId: notificationId,
        );
        AuthService.getInstance().clearNotificationsCache();
        unreadChanged.value++;
      } catch (_) {}
    }

    if (type == 'new_post') {
      final postIdStr = data['post_id'] as String?;
      final postId = postIdStr != null ? int.tryParse(postIdStr) : null;
      if (postId != null) {
        try {
          final post = await PostsService.getInstance().getPost(postId: postId);
          navigatorKey.currentState?.push(
            MaterialPageRoute(builder: (_) => PostDetailScreen(post: post)),
          );
        } catch (_) {}
      }
    }
    // new_deposit / low_balance: mark-as-read is sufficient; user is at /home.
  }
}
