import 'package:shared_preferences/shared_preferences.dart';
import '../models/organisation.dart';
import '../config.dart';

class StorageService {
  static StorageService? _instance;

  late SharedPreferences _prefs;

  static Future<StorageService> getInstance() async {
    if (_instance == null) {
      _instance = StorageService();
      _instance!._prefs = await SharedPreferences.getInstance();
      if (_instance!.organisationId == null) {
        _instance!.setOrganisationId(organisations[0].id);
      }
    }
    return _instance!;
  }

  int? get organisationId {
    return _prefs.getInt('organisation_id');
  }

  Organisation get organisation {
    return organisations.firstWhere(
      (organisation) => organisation.id == organisationId,
    );
  }

  Future setOrganisationId(int? organisationId) async {
    if (organisationId != null) {
      await _prefs.setInt('organisation_id', organisationId);
    } else {
      await _prefs.remove('organisation_id');
    }
  }

  String? get token {
    return _prefs.getString('token');
  }

  Future setToken(String? token) async {
    if (token != null) {
      await _prefs.setString('token', token);
    } else {
      await _prefs.remove('token');
    }
  }

  int? get userId {
    return _prefs.getInt('user_id');
  }

  Future setUserId(int? userId) async {
    if (userId != null) {
      await _prefs.setInt('user_id', userId);
    } else {
      await _prefs.remove('user_id');
    }
  }

  String? get fcmToken {
    return _prefs.getString('fcm_token');
  }

  Future setFcmToken(String? fcmToken) async {
    if (fcmToken != null) {
      await _prefs.setString('fcm_token', fcmToken);
    } else {
      await _prefs.remove('fcm_token');
    }
  }
}
