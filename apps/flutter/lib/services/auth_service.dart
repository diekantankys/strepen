import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart' as http_parser;
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
import 'package:giphy_picker/giphy_picker.dart';
import 'package:image/image.dart';
import 'dart:async';
import 'dart:io';
import 'dart:convert';
import '../models/user.dart';
import '../models/product.dart';
import '../models/transaction.dart';
import '../models/notification.dart';
import 'post_service.dart';
import 'product_service.dart';
import 'settings_service.dart';
import 'storage_service.dart';

class AuthService {
  static AuthService? _instance;

  User? _user;

  List<NotificationData>? _unreadNotifications;

  StreamSubscription<String>? _fcmTokenSubscription;

  Map<int, List<Transaction>> _transactions = {};

  static AuthService getInstance() {
    _instance ??= AuthService();
    return _instance!;
  }

  void clearCache() {
    _user = null;
    _unreadNotifications = null;
    _transactions = {};
  }

  void clearNotificationsCache() {
    _unreadNotifications = null;
  }

  Future<bool> login({required String email, required String password}) async {
    StorageService storage = await StorageService.getInstance();
    final response = await http.post(
      Uri.parse('${storage.organisation.apiUrl}/auth/login'),
      headers: {'X-Api-Key': storage.organisation.apiKey},
      body: {'email': email, 'password': password},
    );
    final data = json.decode(response.body);
    if (!data.containsKey('token')) {
      return false;
    }

    await storage.setToken(data['token']);
    _user = User.fromJson(data['user']);
    await storage.setUserId(_user!.id);
    await registerFcmToken();
    return true;
  }

  Future<void> deregisterFcmToken() async {
    await _fcmTokenSubscription?.cancel();
    _fcmTokenSubscription = null;
    if (Firebase.apps.isEmpty) return;
    final storage = await StorageService.getInstance();
    final fcmToken = storage.fcmToken;
    if (fcmToken == null || storage.userId == null) return;
    try {
      await http.delete(
        Uri.parse(
          '${storage.organisation.apiUrl}/users/${storage.userId!}/fcm-token',
        ),
        headers: {
          'X-Api-Key': storage.organisation.apiKey,
          'Authorization': 'Bearer ${storage.token!}',
        },
        body: {'fcm_token': fcmToken},
      );
    } catch (_) {}
    await storage.setFcmToken(null);
  }

  Future logout() async {
    StorageService storage = await StorageService.getInstance();

    await deregisterFcmToken();

    await http.get(
      Uri.parse('${storage.organisation.apiUrl}/auth/logout'),
      headers: {
        'X-Api-Key': storage.organisation.apiKey,
        'Authorization': 'Bearer ${storage.token!}',
      },
    );
    await storage.setToken(null);
    await storage.setUserId(null);
    clearCache();
    PostsService.getInstance().clearCache();
    ProductsService.getInstance().clearCache();
    SettingsService.getInstance().clearCache();
  }

  Future<bool> check() async {
    if (_user == null) {
      StorageService storage = await StorageService.getInstance();
      if (storage.token == null || storage.userId == null) {
        return false;
      }
      final response = await http.get(
        Uri.parse('${storage.organisation.apiUrl}/users/${storage.userId!}'),
        headers: {
          'X-Api-Key': storage.organisation.apiKey,
          'Authorization': 'Bearer ${storage.token!}',
        },
      );
      try {
        _user = User.fromJson(json.decode(response.body));
      } catch (exception) {
        await storage.setToken(null);
        await storage.setUserId(null);
        return false;
      }
      await registerFcmToken();
    }
    return true;
  }

  Future<void> registerFcmToken() async {
    if (Firebase.apps.isEmpty) return;
    try {
      await FirebaseMessaging.instance.requestPermission();
      final token = await FirebaseMessaging.instance.getToken();
      if (token == null) return;
      await _sendFcmToken(token);
      await _fcmTokenSubscription?.cancel();
      _fcmTokenSubscription = FirebaseMessaging.instance.onTokenRefresh.listen(_sendFcmToken);
    } catch (_) {
      // Best-effort; don't break login flow if FCM token registration fails
    }
  }

  Future<void> _sendFcmToken(String token) async {
    try {
      final storage = await StorageService.getInstance();
      if (storage.token == null || storage.userId == null) return;
      final oldToken = storage.fcmToken;
      await storage.setFcmToken(token);
      await http.post(
        Uri.parse(
          '${storage.organisation.apiUrl}/users/${storage.userId!}/fcm-token',
        ),
        headers: {
          'X-Api-Key': storage.organisation.apiKey,
          'Authorization': 'Bearer ${storage.token!}',
        },
        body: {'fcm_token': token},
      );
      // Remove old token when FCM rotates it so stale entries don't accumulate
      if (oldToken != null && oldToken != token) {
        await http.delete(
          Uri.parse(
            '${storage.organisation.apiUrl}/users/${storage.userId!}/fcm-token',
          ),
          headers: {
            'X-Api-Key': storage.organisation.apiKey,
            'Authorization': 'Bearer ${storage.token!}',
          },
          body: {'fcm_token': oldToken},
        );
      }
    } catch (_) {}
  }

  Future<User?> user({bool forceReload = false}) async {
    if (_user == null || forceReload) {
      StorageService storage = await StorageService.getInstance();
      final response = await http.get(
        Uri.parse('${storage.organisation.apiUrl}/users/${storage.userId!}'),
        headers: {
          'X-Api-Key': storage.organisation.apiKey,
          'Authorization': 'Bearer ${storage.token!}',
        },
      );
      try {
        _user = User.fromJson(json.decode(response.body));
      } catch (exception) {
        return null;
      }
    }
    return _user;
  }

  Future<List<NotificationData>> unreadNotifications({
    bool forceReload = false,
  }) async {
    if (_unreadNotifications == null || forceReload) {
      StorageService storage = await StorageService.getInstance();
      final response = await http.get(
        Uri.parse(
          '${storage.organisation.apiUrl}/users/${storage.userId!}/notifications/unread',
        ),
        headers: {
          'X-Api-Key': storage.organisation.apiKey,
          'Authorization': 'Bearer ${storage.token!}',
        },
      );
      final notificationsJson = json.decode(response.body)['data'];
      _unreadNotifications = notificationsJson
          .map<NotificationData>((json) => NotificationData.fromJson(json))
          .toList();
    }
    return _unreadNotifications!;
  }

  Future readNotification({required String notificationId}) async {
    StorageService storage = await StorageService.getInstance();
    await http.get(
      Uri.parse(
        '${storage.organisation.apiUrl}/notifications/$notificationId/read',
      ),
      headers: {
        'X-Api-Key': storage.organisation.apiKey,
        'Authorization': 'Bearer ${storage.token!}',
      },
    );
  }

  Future<List<Transaction>> transactions({
    int page = 1,
    bool forceReload = false,
  }) async {
    if (!_transactions.containsKey(page) || forceReload) {
      StorageService storage = await StorageService.getInstance();
      final response = await http.get(
        Uri.parse(
          '${storage.organisation.apiUrl}/users/${storage.userId!}/transactions?page=$page',
        ),
        headers: {
          'X-Api-Key': storage.organisation.apiKey,
          'Authorization': 'Bearer ${storage.token!}',
        },
      );
      final transactionsJson = json.decode(response.body)['data'];
      _transactions[page] = transactionsJson
          .map<Transaction>((json) => Transaction.fromJson(json))
          .toList();
    }
    return _transactions[page]!;
  }

  Future<bool> createTransaction({
    required Map<Product, int> productAmounts,
  }) async {
    final body = {
      'name':
          'Mobile transaction on ${DateFormat('yyyy-MM-dd kk:mm:ss').format(DateTime.now())}',
    };

    int index = 0;
    for (Product product in productAmounts.keys) {
      int amount = productAmounts[product]!;
      body['products[$index][product_id]'] = product.id.toString();
      body['products[$index][amount]'] = amount.toString();
      if (_user != null) {
        _user!.balance = _user!.balance! - amount * product.price;
      }
      index++;
    }

    StorageService storage = await StorageService.getInstance();
    final response = await http.post(
      Uri.parse('${storage.organisation.apiUrl}/transactions'),
      headers: {
        'X-Api-Key': storage.organisation.apiKey,
        'Authorization': 'Bearer ${storage.token!}',
      },
      body: body,
    );

    final data = json.decode(response.body);
    if (!data.containsKey('transaction')) {
      return false;
    }

    if (_transactions.containsKey(1)) {
      _transactions[1]!.insert(0, Transaction.fromJson(data['transaction']));
    }
    return true;
  }

  Future<Map<String, List<dynamic>>?> changeDetails({
    required String firstname,
    required String insertion,
    required String lastname,
    required Gender? gender,
    required DateTime? birthday,
    required String email,
    required String phone,
    required String address,
    required String postcode,
    required String city,
  }) async {
    StorageService storage = await StorageService.getInstance();
    final response = await http.post(
      Uri.parse('${storage.organisation.apiUrl}/users/${storage.userId!}/edit'),
      headers: {
        'X-Api-Key': storage.organisation.apiKey,
        'Authorization': 'Bearer ${storage.token!}',
      },
      body: {
        'firstname': firstname,
        'insertion': insertion,
        'lastname': lastname,
        'gender': genderToString(gender),
        'birthday': birthday != null
            ? DateFormat('yyyy-MM-dd').format(birthday)
            : '',
        'email': email,
        'phone': phone,
        'address': address,
        'postcode': postcode,
        'city': city,
      },
    );

    final data = json.decode(response.body);
    if (data.containsKey('user')) {
      _user = User.fromJson(data['user']);
      return null;
    }
    return Map<String, List<dynamic>>.from(data['errors']);
  }

  Future<void> changeNotificationSettings({
    required bool notifyNewPosts,
    required bool notifyLowBalance,
    required bool notifyNewDeposits,
    required bool notifyNewTransactions,
    required bool notifyByEmail,
  }) async {
    try {
      final storage = await StorageService.getInstance();
      if (storage.token == null || storage.userId == null) return;
      final response = await http.post(
        Uri.parse('${storage.organisation.apiUrl}/users/${storage.userId!}/edit'),
        headers: {
          'X-Api-Key': storage.organisation.apiKey,
          'Authorization': 'Bearer ${storage.token!}',
        },
        body: {
          'notify_new_posts': notifyNewPosts.toString(),
          'notify_low_balance': notifyLowBalance.toString(),
          'notify_new_deposits': notifyNewDeposits.toString(),
          'notify_new_transactions': notifyNewTransactions.toString(),
          'notify_by_email': notifyByEmail.toString(),
        },
      );
      final data = json.decode(response.body);
      if (data.containsKey('user')) {
        _user = User.fromJson(data['user']);
      }
    } catch (_) {}
  }

  Future<bool> changeAvatar({required XFile? avatar}) async {
    StorageService storage = await StorageService.getInstance();
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('${storage.organisation.apiUrl}/users/${storage.userId!}/edit'),
    );
    request.headers['X-Api-Key'] = storage.organisation.apiKey;
    request.headers['Authorization'] = 'Bearer ${storage.token!}';
    if (avatar != null) {
      // Load and resize image
      final avatarImage = decodeImage(File(avatar.path).readAsBytesSync())!;
      final resizedAvatarImage = copyResize(
        avatarImage,
        width: 512,
        height: 512,
      );
      request.files.add(
        http.MultipartFile.fromBytes(
          'avatar',
          encodeJpg(resizedAvatarImage, quality: 75),
          filename: 'avatar.jpg',
          contentType: http_parser.MediaType('image', 'jpeg'),
        ),
      );
    } else {
      request.fields['avatar'] = 'null';
    }
    final response = await request.send();
    final body = await response.stream.bytesToString();

    final data = json.decode(body);
    if (data.containsKey('user')) {
      _user = User.fromJson(data['user']);
      return true;
    }
    return false;
  }

  Future<bool> changeThanks({required GiphyGif? thanks}) async {
    StorageService storage = await StorageService.getInstance();
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('${storage.organisation.apiUrl}/users/${storage.userId!}/edit'),
    );
    request.headers['X-Api-Key'] = storage.organisation.apiKey;
    request.headers['Authorization'] = 'Bearer ${storage.token!}';
    if (thanks != null) {
      // Download gif image
      final response = await http.get(Uri.parse(thanks.images.downsized!.url!));
      request.files.add(
        http.MultipartFile.fromBytes(
          'thanks',
          response.bodyBytes,
          filename: 'thanks.gif',
          contentType: http_parser.MediaType('image', 'gif'),
        ),
      );
    } else {
      request.fields['thanks'] = 'null';
    }
    final response = await request.send();
    final body = await response.stream.bytesToString();

    final data = json.decode(body);
    if (data.containsKey('user')) {
      _user = User.fromJson(data['user']);
      return true;
    }
    return false;
  }

  Future<Map<String, List<dynamic>>?> changePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    StorageService storage = await StorageService.getInstance();
    final response = await http.post(
      Uri.parse('${storage.organisation.apiUrl}/users/${storage.userId!}/edit'),
      headers: {
        'X-Api-Key': storage.organisation.apiKey,
        'Authorization': 'Bearer ${storage.token!}',
      },
      body: {
        'current_password': currentPassword,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );

    final data = json.decode(response.body);
    if (data.containsKey('user')) {
      _user = User.fromJson(data['user']);
      return null;
    }
    return Map<String, List<dynamic>>.from(data['errors']);
  }
}
