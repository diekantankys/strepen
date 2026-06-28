import 'package:flutter/material.dart';
import '../l10n/app_localizations.dart';
import 'settings_screen_avatar_tab.dart';
import 'settings_screen_details_tab.dart';
import 'settings_screen_password_tab.dart';
import 'settings_screen_thanks_tab.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return DefaultTabController(
      length: 4,
      child: Scaffold(
        appBar: AppBar(
          title: Text(lang.settings_header),
          bottom: TabBar(
            isScrollable: true,
            tabs: [
              Tab(text: lang.settings_details_tab),
              Tab(text: lang.settings_avatar_tab),
              Tab(text: lang.settings_thanks_tab),
              Tab(text: lang.settings_password_tab),
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
            Center(
              child: SingleChildScrollView(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  child: const SettingsChangePasswordTab(),
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
  final void Function()? onTap;
  final EdgeInsets margin;

  const InputField({
    required this.controller,
    required this.label,
    this.error,
    this.autocorrect = true,
    this.obscureText = false,
    this.enabled = true,
    this.onTap,
    this.margin = const EdgeInsets.only(bottom: 16),
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      child: onTap != null
          ? InkWell(
              onTap: onTap,
              customBorder: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              child: TextField(
                controller: controller,
                autocorrect: autocorrect,
                obscureText: obscureText,
                enabled: enabled,
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
            )
          : TextField(
              controller: controller,
              autocorrect: autocorrect,
              obscureText: obscureText,
              enabled: enabled,
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
