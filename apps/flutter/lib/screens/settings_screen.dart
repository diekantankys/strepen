import 'package:flutter/material.dart';
import '../l10n/app_localizations.dart';
import 'settings_screen_avatar_tab.dart';
import 'settings_screen_details_tab.dart';
import 'settings_screen_notifications_tab.dart';
import 'settings_screen_password_tab.dart';
import 'settings_screen_thanks_tab.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return DefaultTabController(
      length: 5,
      child: Scaffold(
        appBar: AppBar(
          title: Text(lang.settings_header),
          bottom: TabBar(
            isScrollable: true,
            tabs: [
              Tab(text: lang.settings_details_tab),
              Tab(text: lang.settings_notifications_tab),
              Tab(text: lang.settings_password_tab),
              Tab(text: lang.settings_avatar_tab),
              Tab(text: lang.settings_thanks_tab),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            Center(
              child: SingleChildScrollView(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  child: const SettingsChangeDetailsTab(),
                ),
              ),
            ),
            Center(
              child: SingleChildScrollView(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  child: const SettingsNotificationsTab(),
                ),
              ),
            ),
            Center(
              child: SingleChildScrollView(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  child: const SettingsChangePasswordTab(),
                ),
              ),
            ),
            Center(
              child: SingleChildScrollView(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  child: const SettingsChangeAvatarTab(),
                ),
              ),
            ),
            Center(
              child: SingleChildScrollView(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  child: const SettingsChangeThanksTab(),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class InputField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String? error;
  final bool autocorrect;
  final bool obscureText;
  final bool enabled;
  final bool readOnly;
  final void Function()? onTap;
  final EdgeInsets margin;

  const InputField({
    required this.controller,
    required this.label,
    this.error,
    this.autocorrect = true,
    this.obscureText = false,
    this.enabled = true,
    this.readOnly = false,
    this.onTap,
    this.margin = const EdgeInsets.only(bottom: 16),
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      child: TextField(
        controller: controller,
        autocorrect: autocorrect,
        obscureText: obscureText,
        enabled: enabled,
        readOnly: readOnly,
        showCursor: readOnly ? false : null,
        onTap: onTap,
        style: const TextStyle(fontSize: 16),
        decoration: InputDecoration(
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 24,
            vertical: 16,
          ),
          labelText: label,
          errorText: error,
        ),
      ),
    );
  }
}
