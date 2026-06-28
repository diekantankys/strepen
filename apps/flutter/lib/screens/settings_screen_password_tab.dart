import 'package:flutter/material.dart';
import '../l10n/app_localizations.dart';
import '../services/auth_service.dart';
import 'settings_screen.dart';

class SettingsChangePasswordTab extends StatefulWidget {
  const SettingsChangePasswordTab({super.key});

  @override
  State createState() {
    return _SettingsChangePasswordTabState();
  }
}

class _SettingsChangePasswordTabState extends State {
  bool _isLoading = false;
  final _currentPasswordController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordConfirmationController = TextEditingController();
  String? _currentPasswordError;
  String? _passwordError;
  String? _passwordConfirmationError;

  @override
  void dispose() {
    _currentPasswordController.dispose();
    _passwordController.dispose();
    _passwordConfirmationController.dispose();
    super.dispose();
  }

  Future<void> changePassword() async {
    final lang = AppLocalizations.of(context)!;
    setState(() => _isLoading = true);

    Map<String, List<dynamic>>? errors = await AuthService.getInstance()
        .changePassword(
          currentPassword: _currentPasswordController.text,
          password: _passwordController.text,
          passwordConfirmation: _passwordConfirmationController.text,
        );

    // When there are errors
    if (errors != null) {
      setState(() {
        _currentPasswordError = errors.containsKey('current_password')
            ? errors['current_password']![0]!
            : null;
        _passwordError = errors.containsKey('password')
            ? errors['password']![0]!
            : null;
        _passwordConfirmationError = errors.containsKey('password_confirmation')
            ? errors['password_confirmation']![0]!
            : null;
        _isLoading = false;
      });
      return;
    }

    // When successfull
    setState(() {
      _currentPasswordController.text = '';
      _currentPasswordError = null;
      _passwordController.text = '';
      _passwordError = null;
      _passwordConfirmationController.text = '';
      _passwordConfirmationError = null;
      _isLoading = false;
    });

    if (!mounted) return;
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Text(lang.settings_password_success_header),
          content: Text(lang.settings_password_success_description),
          actions: [
            TextButton(
              child: Text(lang.settings_password_success_ok),
              onPressed: () => Navigator.of(context).pop(),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Container(
              width: double.infinity,
              margin: const EdgeInsets.only(bottom: 16),
              child: Text(
                lang.settings_password_header,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            InputField(
              controller: _currentPasswordController,
              label: lang.settings_password_current_password,
              error: _currentPasswordError,
              autocorrect: false,
              obscureText: true,
            ),
            InputField(
              controller: _passwordController,
              label: lang.settings_password_password,
              error: _passwordError,
              autocorrect: false,
              obscureText: true,
            ),
            InputField(
              controller: _passwordConfirmationController,
              label: lang.settings_password_password_confirmation,
              error: _passwordConfirmationError,
              autocorrect: false,
              obscureText: true,
            ),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isLoading ? null : changePassword,
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
                  lang.settings_password_header,
                  style: const TextStyle(color: Colors.white, fontSize: 18),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
