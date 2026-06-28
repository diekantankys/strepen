import 'package:flutter/material.dart';
import '../l10n/app_localizations.dart';
import '../services/auth_service.dart';

class LoadingScreen extends StatefulWidget {
  const LoadingScreen({super.key});

  @override
  State createState() {
    return _LoadingScreenState();
  }
}

class _LoadingScreenState extends State {
  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(title: Text(lang.loading_header)),
      body: FutureBuilder<bool>(
        future: AuthService.getInstance().check(),
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            print('LoadingScreen error: ${snapshot.error}');
            return Center(child: Text(lang.loading_error));
          } else if (snapshot.hasData) {
            Future(() {
              if (!mounted) return;
              Navigator.pushNamedAndRemoveUntil(
                this.context,
                snapshot.data! ? '/home' : '/login',
                (route) => false,
              );
            });
          }
          return const Center(child: CircularProgressIndicator());
        },
      ),
    );
  }
}
