import 'dart:convert';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, TargetPlatform;
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'config.dart';
import 'l10n/app_localizations.dart';
import 'screens/home_screen.dart';
import 'screens/loading_screen.dart';
import 'screens/login_screen.dart';
import 'screens/settings_screen.dart';
import 'services/notification_service.dart';

final FlutterLocalNotificationsPlugin localNotifications =
    FlutterLocalNotificationsPlugin();

const AndroidNotificationChannel _channel = AndroidNotificationChannel(
  'strepen_notifications',
  'Strepen meldingen',
  importance: Importance.high,
);

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // Background handler - Firebase is already initialized by the system
}

// Only getInitialMessage runs after runApp: it hangs on iOS with
// FlutterImplicitEngineDelegate until the notification system is ready.
// Everything else must be registered before runApp so foreground pushes
// are never missed on Android.
Future<void> _handleColdStart() async {
  final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
  if (initialMessage != null) {
    NotificationService.getInstance().pendingData = initialMessage.data;
  }
}

class StrepenApp extends StatelessWidget {
  const StrepenApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: NotificationService.getInstance().navigatorKey,
      title: 'Strepen',
      debugShowCheckedModeBanner: false,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      theme: ThemeData(
        useMaterial3: false,
        colorScheme: ColorScheme.fromSwatch(
          primarySwatch: Colors.pink,
        ).copyWith(secondary: Colors.pink),
      ),
      darkTheme: ThemeData(
        useMaterial3: false,
        colorScheme: ColorScheme.fromSwatch(
          brightness: Brightness.dark,
          primarySwatch: Colors.pink,
        ).copyWith(secondary: Colors.pink),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.pink,
          foregroundColor: Colors.white,
        ),
      ),
      initialRoute: '/loading',
      routes: {
        '/loading': (context) => const LoadingScreen(),
        '/home': (context) => const HomeScreen(),
        '/login': (context) => const LoginScreen(),
        '/settings': (context) => const SettingsScreen(),
      },
    );
  }
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final options = defaultTargetPlatform == TargetPlatform.android
      ? firebaseAndroidOptions
      : defaultTargetPlatform == TargetPlatform.iOS
      ? firebaseIosOptions
      : null;

  if (options != null) {
    try {
      await Firebase.initializeApp(options: options);
      debugPrint('Firebase push notifications enabled');
    } catch (e) {
      debugPrint('Firebase push notifications disabled: $e');
    }
  } else {
    debugPrint(
      'Firebase push notifications disabled: no configuration provided',
    );
  }

  if (Firebase.apps.isNotEmpty) {
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

    // Background: app was backgrounded, user tapped the notification
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      NotificationService.getInstance().handleData(message.data);
    });

    await localNotifications.initialize(
      InitializationSettings(
        android: const AndroidInitializationSettings(
          '@drawable/ic_notification',
        ),
        iOS: const DarwinInitializationSettings(),
      ),
      // Foreground: user tapped the local notification shown while app was open
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        final payload = response.payload;
        if (payload == null) return;
        try {
          final data = Map<String, dynamic>.from(jsonDecode(payload) as Map);
          NotificationService.getInstance().handleData(data);
        } catch (_) {}
      },
    );

    await localNotifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.createNotificationChannel(_channel);

    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      final notification = message.notification;
      if (notification == null) return;
      localNotifications.show(
        notification.hashCode,
        notification.title,
        notification.body,
        NotificationDetails(
          android: AndroidNotificationDetails(
            _channel.id,
            _channel.name,
            icon: '@drawable/ic_notification',
          ),
          iOS: const DarwinNotificationDetails(),
        ),
        payload: jsonEncode(message.data),
      );
    });
  }

  runApp(const StrepenApp());

  // getInitialMessage after runApp: hangs on iOS until the notification system
  // is ready (FlutterImplicitEngineDelegate registers plugins late).
  if (Firebase.apps.isNotEmpty) {
    _handleColdStart();
  }
}
