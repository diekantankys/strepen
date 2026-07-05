import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import '../l10n/app_localizations.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/storage_service.dart';

class SettingsNotificationsTab extends StatefulWidget {
  const SettingsNotificationsTab({super.key});

  @override
  State createState() => _SettingsNotificationsTabState();
}

class _SettingsNotificationsTabState extends State {
  bool _isInitialized = false;
  bool _isSaving = false;
  bool _isPushLoading = false;

  bool _notifyNewPosts = true;
  bool _notifyLowBalance = true;
  bool _notifyNewDeposits = true;
  bool _notifyNewTransactions = false;
  bool _notifyByEmail = true;

  bool _pushEnabled = false;
  bool _pushPermissionDenied = false;

  @override
  void initState() {
    super.initState();
    _loadPushState();
  }

  Future<void> _loadPushState() async {
    if (Firebase.apps.isEmpty) return;
    final storage = await StorageService.getInstance();
    final settings = await FirebaseMessaging.instance.getNotificationSettings();
    setState(() {
      _pushEnabled = storage.fcmToken != null;
      _pushPermissionDenied =
          settings.authorizationStatus == AuthorizationStatus.denied;
    });
  }

  Future<void> _saveNotifications() async {
    setState(() => _isSaving = true);
    await AuthService.getInstance().changeNotificationSettings(
      notifyNewPosts: _notifyNewPosts,
      notifyLowBalance: _notifyLowBalance,
      notifyNewDeposits: _notifyNewDeposits,
      notifyNewTransactions: _notifyNewTransactions,
      notifyByEmail: _notifyByEmail,
    );
    setState(() => _isSaving = false);
    if (!mounted) return;
    final lang = AppLocalizations.of(context)!;
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(lang.settings_notifications_success_header),
        content: Text(lang.settings_notifications_success_description),
        actions: [
          TextButton(
            child: Text(lang.settings_notifications_success_ok),
            onPressed: () => Navigator.of(context).pop(),
          ),
        ],
      ),
    );
  }

  Future<void> _setPushEnabled(bool enabled) async {
    if (Firebase.apps.isEmpty) return;
    setState(() => _isPushLoading = true);

    if (enabled) {
      final settings = await FirebaseMessaging.instance.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.denied) {
        setState(() {
          _pushEnabled = false;
          _pushPermissionDenied = true;
          _isPushLoading = false;
        });
        return;
      }
      await AuthService.getInstance().registerFcmToken();
      final storage = await StorageService.getInstance();
      setState(() {
        _pushEnabled = storage.fcmToken != null;
        _isPushLoading = false;
      });
    } else {
      await AuthService.getInstance().deregisterFcmToken();
      setState(() {
        _pushEnabled = false;
        _isPushLoading = false;
      });
    }
  }

  Widget _switchRow(String label, bool value, void Function(bool)? onChanged) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: onChanged != null ? () => onChanged(!value) : null,
        borderRadius: BorderRadius.circular(4),
        child: Row(
          children: [
            Expanded(
              child: Text(
                label,
                style: TextStyle(
                  fontSize: 14,
                  color: onChanged == null ? Colors.grey : null,
                ),
              ),
            ),
            Switch(
              activeThumbColor: Colors.pink,
              value: value,
              onChanged: onChanged,
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return FutureBuilder<User?>(
      future: AuthService.getInstance().user(),
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return Center(child: Text(lang.settings_details_error));
        } else if (snapshot.hasData) {
          final user = snapshot.data!;
          if (!_isInitialized) {
            _isInitialized = true;
            _notifyNewPosts = user.notifyNewPosts ?? true;
            _notifyLowBalance = user.notifyLowBalance ?? true;
            _notifyNewDeposits = user.notifyNewDeposits ?? true;
            _notifyNewTransactions = user.notifyNewTransactions ?? false;
            _notifyByEmail = user.notifyByEmail ?? true;
          }

          return Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 16),
                    child: Text(
                      lang.settings_notifications_header,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  _switchRow(
                    lang.settings_notifications_notify_new_posts,
                    _notifyNewPosts,
                    (v) => setState(() => _notifyNewPosts = v),
                  ),
                  _switchRow(
                    lang.settings_notifications_low_balance,
                    _notifyLowBalance,
                    (v) => setState(() => _notifyLowBalance = v),
                  ),
                  _switchRow(
                    lang.settings_notifications_new_deposits,
                    _notifyNewDeposits,
                    (v) => setState(() => _notifyNewDeposits = v),
                  ),
                  _switchRow(
                    lang.settings_notifications_new_transactions,
                    _notifyNewTransactions,
                    (v) => setState(() => _notifyNewTransactions = v),
                  ),
                  const Divider(),
                  if (Firebase.apps.isNotEmpty) ...[
                    _switchRow(
                      lang.settings_push_toggle,
                      _pushEnabled,
                      _pushPermissionDenied || _isPushLoading
                          ? null
                          : _setPushEnabled,
                    ),
                    if (_pushPermissionDenied)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Text(
                          lang.settings_push_denied_body,
                          style: const TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                      ),
                  ],
                  _switchRow(
                    lang.settings_notifications_by_email,
                    _notifyByEmail,
                    (v) => setState(() => _notifyByEmail = v),
                  ),
                  const SizedBox(height: 8),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _isSaving ? null : _saveNotifications,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.pink,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 24,
                          vertical: 16,
                        ),
                      ),
                      child: Text(
                        lang.settings_notifications_save,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        } else {
          return const Center(child: CircularProgressIndicator());
        }
      },
    );
  }
}
