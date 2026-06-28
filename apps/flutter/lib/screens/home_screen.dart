import 'package:flutter/material.dart';
import 'home_screen_posts_tab.dart';
import 'home_screen_stripe_tab.dart';
import 'home_screen_history_tab.dart';
import 'home_screen_profile_tab.dart';
import '../l10n/app_localizations.dart';
import '../models/notification.dart';
import '../services/auth_service.dart';
import '../services/notification_service.dart';
import '../services/settings_service.dart';
import 'post_detail_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State createState() {
    return _HomeScreenState();
  }
}

class _HomeScreenState extends State {
  final _pageController = PageController(initialPage: 1);

  int _currentPageIndex = 1;

  @override
  void initState() {
    super.initState();
    final pending = NotificationService.getInstance().pendingData;
    if (pending != null) {
      NotificationService.getInstance().pendingData = null;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        NotificationService.getInstance().handleData(pending);
      });
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(
          [
            lang.home_posts,
            lang.home_stripe,
            lang.home_history,
            lang.home_profile,
          ][_currentPageIndex],
        ),
        actions: [NotificationsButton(pageController: _pageController)],
      ),
      body: PageView(
        controller: _pageController,
        onPageChanged: (index) {
          setState(() => _currentPageIndex = index);
        },
        children: const [
          HomeScreenPostsTab(),
          HomeScreenStripeTab(),
          HomeScreenHistoryTab(),
          HomeScreenProfileTab(),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        type: BottomNavigationBarType.fixed,
        onTap: (index) {
          _pageController.animateToPage(
            index,
            duration: const Duration(milliseconds: 300),
            curve: Curves.ease,
          );
          setState(() => _currentPageIndex = index);
        },
        currentIndex: _currentPageIndex,
        items: [
          BottomNavigationBarItem(
            icon: const Icon(Icons.email),
            label: lang.home_posts_short,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.edit),
            label: lang.home_stripe_short,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.history),
            label: lang.home_history_short,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.person),
            label: lang.home_profile_short,
          ),
        ],
      ),
    );
  }
}

class NotificationsButton extends StatefulWidget {
  final PageController pageController;

  const NotificationsButton({super.key, required this.pageController});

  @override
  State createState() {
    return _NotificationsButtonState();
  }
}

class _NotificationsButtonState extends State<NotificationsButton> {
  bool _forceReload = false;

  @override
  void initState() {
    super.initState();
    NotificationService.getInstance().unreadChanged.addListener(_onUnreadChanged);
  }

  @override
  void dispose() {
    NotificationService.getInstance().unreadChanged.removeListener(_onUnreadChanged);
    super.dispose();
  }

  void _onUnreadChanged() {
    if (mounted) setState(() => _forceReload = true);
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return FutureBuilder<List<dynamic>>(
      future: Future.wait([
        AuthService.getInstance().unreadNotifications(
          forceReload: _forceReload,
        ),
        SettingsService.getInstance().settings(),
      ]),
      builder: (context, snapshot) {
        if (snapshot.hasData) {
          List<NotificationData> notifications = snapshot.data![0]!;
          Map<String, dynamic> settings = snapshot.data![1]!;
          return PopupMenuButton(
            icon: Icon(
              notifications.isNotEmpty
                  ? Icons.notifications_on
                  : Icons.notifications_sharp,
            ),
            tooltip: lang.home_notifications,
            itemBuilder: (BuildContext context) {
              if (notifications.isNotEmpty) {
                return notifications.take(5).map((
                  NotificationData notification,
                ) {
                  if (notification.type == NotificationType.newDeposit) {
                    return PopupMenuItem(
                      onTap: () async {
                        await AuthService.getInstance().readNotification(
                          notificationId: notification.id,
                        );
                        setState(() => _forceReload = true);
                        widget.pageController.animateToPage(
                          3,
                          duration: const Duration(milliseconds: 300),
                          curve: Curves.ease,
                        );
                      },
                      child: Text(
                        lang.home_new_deposit(
                          '${settings['currency_symbol']} ${notification.data['amount'].toStringAsFixed(2)}',
                        ),
                      ),
                    );
                  }

                  if (notification.type == NotificationType.newPost) {
                    return PopupMenuItem(
                      onTap: () async {
                        await AuthService.getInstance().readNotification(
                          notificationId: notification.id,
                        );
                        if (!mounted) return;
                        setState(() => _forceReload = true);
                        final post = notification.post;
                        if (post != null) {
                          WidgetsBinding.instance.addPostFrameCallback((_) {
                            if (!mounted) return;
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => PostDetailScreen(post: post),
                              ),
                            );
                          });
                        } else {
                          widget.pageController.animateToPage(
                            0,
                            duration: const Duration(milliseconds: 300),
                            curve: Curves.ease,
                          );
                        }
                      },
                      child: Text(lang.home_new_post),
                    );
                  }

                  if (notification.type == NotificationType.lowBalance) {
                    return PopupMenuItem(
                      onTap: () async {
                        await AuthService.getInstance().readNotification(
                          notificationId: notification.id,
                        );
                        setState(() => _forceReload = true);
                        widget.pageController.animateToPage(
                          3,
                          duration: const Duration(milliseconds: 300),
                          curve: Curves.ease,
                        );
                      },
                      child: Text(
                        lang.home_low_balance(
                          '${settings['currency_symbol']} ${notification.data['balance'].toStringAsFixed(2)}',
                        ),
                      ),
                    );
                  }

                  if (notification.type == NotificationType.newTransaction) {
                    return PopupMenuItem(
                      onTap: () async {
                        await AuthService.getInstance().readNotification(
                          notificationId: notification.id,
                        );
                        setState(() => _forceReload = true);
                        widget.pageController.animateToPage(
                          3,
                          duration: const Duration(milliseconds: 300),
                          curve: Curves.ease,
                        );
                      },
                      child: Text(
                        lang.home_new_transaction(
                          '${settings['currency_symbol']} ${notification.data['amount'].toStringAsFixed(2)}',
                        ),
                      ),
                    );
                  }

                  return PopupMenuItem(
                    onTap: () async {
                      await AuthService.getInstance().readNotification(
                        notificationId: notification.id,
                      );
                      setState(() => _forceReload = true);
                    },
                    child: Text(lang.home_unknown_notification),
                  );
                }).toList();
              } else {
                return [
                  PopupMenuItem(
                    onTap: () async {
                      setState(() => _forceReload = true);
                    },
                    child: Text(
                      lang.home_unread_notifications_empty,
                      style: const TextStyle(
                        color: Colors.grey,
                        fontStyle: FontStyle.italic,
                      ),
                    ),
                  ),
                ];
              }
            },
          );
        } else {
          if (snapshot.hasError) {
            print('NotificationsButton error: ${snapshot.error}');
          }
          return PopupMenuButton(
            icon: const Icon(Icons.notifications_sharp),
            tooltip: lang.home_notifications,
            itemBuilder: (BuildContext context) {
              return [
                PopupMenuItem(
                  onTap: () async {
                    setState(() => _forceReload = true);
                  },
                  child: Text(
                    lang.home_unread_notifications_empty,
                    style: const TextStyle(
                      color: Colors.grey,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ),
              ];
            },
          );
        }
      },
    );
  }
}
