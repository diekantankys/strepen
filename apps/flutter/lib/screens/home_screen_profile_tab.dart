import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../l10n/app_localizations.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/settings_service.dart';

class HomeScreenProfileTab extends StatefulWidget {
  const HomeScreenProfileTab({super.key});

  @override
  State createState() {
    return _HomeScreenProfileTabState();
  }
}

class _HomeScreenProfileTabState extends State {
  bool _forceReload = false;

  bool _isLoading = false;

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return FutureBuilder<List<dynamic>>(
      future: Future.wait([
        AuthService.getInstance().user(forceReload: _forceReload),
        SettingsService.getInstance().settings(),
      ]),
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          print('HomeScreenProfileTab error: ${snapshot.error}');
          return Center(child: Text(lang.home_profile_error));
        } else if (snapshot.hasData) {
          User user = snapshot.data![0]!;
          Map<String, dynamic> settings = snapshot.data![1]!;
          return RefreshIndicator(
            onRefresh: () async {
              setState(() => _forceReload = true);
            },
            child: Center(
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        margin: const EdgeInsets.symmetric(vertical: 16),
                        child: SizedBox(
                          width: 192,
                          height: 192,
                          child: Card(
                            clipBehavior: Clip.antiAliasWithSaveLayer,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(96),
                            ),
                            elevation: 3,
                            child: Container(
                              decoration: BoxDecoration(
                                image: DecorationImage(
                                  fit: BoxFit.cover,
                                  image: CachedNetworkImageProvider(
                                    user.avatar,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                      Container(
                        margin: const EdgeInsets.symmetric(vertical: 8),
                        child: Text(
                          user.name,
                          style: const TextStyle(
                            fontSize: 32,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                      InkWell(
                        onTap: () {
                          setState(() => _forceReload = true);
                        },
                        child: Container(
                          width: double.infinity,
                          margin: const EdgeInsets.symmetric(vertical: 8),
                          child: Text(
                            '${settings['currency_symbol']} ${user.balance!.toStringAsFixed(2)}',
                            style: TextStyle(
                              fontSize: 24,
                              color: user.balance! < 0 ? Colors.red : null,
                              fontWeight: FontWeight.w500,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ),
                      Container(
                        margin: const EdgeInsets.symmetric(vertical: 16),
                        child: Row(
                          children: [
                            // Settings button
                            Expanded(
                              flex: 1,
                              child: ElevatedButton(
                                onPressed: () {
                                  Navigator.pushNamed(context, '/settings');
                                },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.pink,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(48),
                                  ),
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 24,
                                    vertical: 16,
                                  ),
                                ),
                                child: Text(
                                  lang.home_profile_settings,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                  ),
                                ),
                              ),
                            ),

                            const SizedBox(width: 16),

                            // Logout button
                            Expanded(
                              flex: 1,
                              child: ElevatedButton(
                                onPressed: _isLoading
                                    ? null
                                    : () async {
                                        setState(() => _isLoading = true);
                                        await AuthService.getInstance()
                                            .logout();
                                        if (!mounted) return;
                                        Navigator.pushNamedAndRemoveUntil(
                                          this.context,
                                          '/login',
                                          (route) => false,
                                        );
                                      },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.pink,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(48),
                                  ),
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 24,
                                    vertical: 16,
                                  ),
                                ),
                                child: Text(
                                  lang.home_profile_logout,
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
                    ],
                  ),
                ),
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
