import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:giphy_picker/giphy_picker.dart';
import '../l10n/app_localizations.dart';
import '../models/user.dart';
import '../config.dart';
import '../services/auth_service.dart';

class SettingsChangeThanksTab extends StatefulWidget {
  const SettingsChangeThanksTab({super.key});

  @override
  State createState() {
    return _SettingsChangeThanksTabState();
  }
}

class _SettingsChangeThanksTabState extends State {
  bool _isLoading = false;

  Future<void> searchThanks() async {
    final lang = AppLocalizations.of(context)!;

    // Show giphy picker
    GiphyGif? thanksGif = await GiphyPicker.pickGif(
      context: context,
      apiKey: giphyApiKey,
      appBarBuilder: (context, {actions, title}) =>
          AppBar(title: title, actions: actions),
    );
    if (thanksGif == null) {
      setState(() => _isLoading = false);
      return;
    }

    // Upload image to server
    print(
      'SettingsChangeThanksTab: Selected thanks gif: ${thanksGif.images.original!.url}',
    );
    if (await AuthService.getInstance().changeThanks(thanks: thanksGif)) {
      // Show success dialog
      setState(() => _isLoading = false);
      if (!mounted) return;
      showDialog(
        context: context,
        builder: (BuildContext context) {
          return AlertDialog(
            title: Text(lang.settings_thanks_success_header),
            content: Text(lang.settings_thanks_success_description),
            actions: [
              TextButton(
                child: Text(lang.settings_thanks_success_ok),
                onPressed: () => Navigator.of(context).pop(),
              ),
            ],
          );
        },
      );
    } else {
      // Show error dialog
      setState(() => _isLoading = false);
      if (!mounted) return;
      showDialog(
        context: context,
        builder: (BuildContext context) {
          return AlertDialog(
            title: Text(lang.settings_thanks_error_header),
            content: Text(lang.settings_thanks_error_description),
            actions: [
              TextButton(
                child: Text(lang.settings_thanks_success_ok),
                onPressed: () => Navigator.of(context).pop(),
              ),
            ],
          );
        },
      );
    }
  }

  Future<void> deleteThanks() async {
    final lang = AppLocalizations.of(context)!;

    setState(() => _isLoading = true);
    if (await AuthService.getInstance().changeThanks(thanks: null)) {
      // Show success dialog
      setState(() => _isLoading = false);
      if (!mounted) return;
      showDialog(
        context: context,
        builder: (BuildContext context) {
          return AlertDialog(
            title: Text(lang.settings_thanks_success_header),
            content: Text(lang.settings_thanks_success_description),
            actions: [
              TextButton(
                child: Text(lang.settings_thanks_success_ok),
                onPressed: () => Navigator.of(context).pop(),
              ),
            ],
          );
        },
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return FutureBuilder<User?>(
      future: AuthService.getInstance().user(),
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          print('SettingsScreenThanksTab error: ${snapshot.error}');
          return Center(child: Text(lang.settings_avatar_error));
        } else if (snapshot.hasData) {
          User user = snapshot.data!;
          return Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 16),
                    child: Text(
                      lang.settings_thanks_header,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),

                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 16),
                    child: Text(
                      !user.thanks.contains('default.gif')
                          ? lang.settings_thanks_has_thanks
                          : lang.settings_thanks_no_thanks,
                      style: const TextStyle(fontSize: 16),
                    ),
                  ),

                  // User thanks
                  Container(
                    margin: const EdgeInsets.only(bottom: 16),
                    child: SizedBox(
                      width: 256,
                      height: 256,
                      child: Card(
                        clipBehavior: Clip.antiAliasWithSaveLayer,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                        elevation: 3,
                        child: Container(
                          decoration: BoxDecoration(
                            image: DecorationImage(
                              fit: BoxFit.cover,
                              image: CachedNetworkImageProvider(user.thanks),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),

                  // Search thanks button
                  if (giphyApiKey.isNotEmpty) ...[
                    Container(
                      margin: const EdgeInsets.only(bottom: 16),
                      child: SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _isLoading ? null : searchThanks,
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
                            lang.settings_thanks_search_button,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 18,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],

                  // Delete thanks button
                  if (!user.thanks.contains('default.gif')) ...[
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : deleteThanks,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 24,
                            vertical: 16,
                          ),
                        ),
                        child: Text(
                          lang.settings_thanks_delete_button,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                          ),
                        ),
                      ),
                    ),
                  ],
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
