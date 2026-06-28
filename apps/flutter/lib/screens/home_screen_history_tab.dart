import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../l10n/app_localizations.dart';
import '../models/transaction.dart';
import '../services/auth_service.dart';
import '../services/settings_service.dart';
import 'home_screen_stripe_tab.dart';

class HomeScreenHistoryTab extends StatefulWidget {
  const HomeScreenHistoryTab({super.key});

  @override
  State createState() {
    return _HomeScreenHistoryTabState();
  }
}

class _HomeScreenHistoryTabState extends State {
  final ScrollController _scrollController = ScrollController();

  List<Transaction> _transactions = [];
  final List<int> _loadedPages = [];
  int _page = 1;
  bool _isLoading = true;
  bool _hasError = false;
  bool _isDone = false;

  @override
  void initState() {
    super.initState();
    loadNextPage();
    _scrollController.addListener(() {
      if (!_isLoading &&
          _scrollController.position.pixels >
              _scrollController.position.maxScrollExtent * 0.9) {
        loadNextPage();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void loadNextPage() async {
    if (_isDone) return;

    _isLoading = true;
    List<Transaction> newTransactions;
    try {
      newTransactions = await AuthService.getInstance().transactions(
        page: _page,
        forceReload: _loadedPages.contains(_page),
      );
      if (!_loadedPages.contains(_page)) {
        _loadedPages.add(_page);
      }
    } catch (exception, stacktrace) {
      print(exception);
      print(stacktrace);

      _isLoading = false;
      if (mounted) {
        setState(() => _hasError = true);
      }
      return;
    }
    if (newTransactions.isNotEmpty) {
      _transactions.addAll(newTransactions);
      _page++;
    } else {
      _isDone = true;
    }

    _isLoading = false;
    if (newTransactions.isNotEmpty && mounted) {
      setState(() => _transactions = _transactions);
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return FutureBuilder<Map<String, dynamic>>(
      future: SettingsService.getInstance().settings(),
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          print('HomeScreenHistoryTab error: ${snapshot.error}');
          return Center(child: Text(lang.home_history_error));
        } else if (snapshot.hasData) {
          Map<String, dynamic> settings = snapshot.data!;
          return RefreshIndicator(
            onRefresh: () async {
              _transactions = [];
              _page = 1;
              _isLoading = false;
              _isDone = false;
              loadNextPage();
            },
            child: _hasError
                ? Center(child: Text(lang.home_history_error))
                : (_transactions.isNotEmpty
                      ? ListView.builder(
                          controller: _scrollController,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 8,
                          ),
                          itemCount: _transactions.length,
                          itemBuilder: (context, index) => TransactionItem(
                            transaction: _transactions[index],
                            settings: settings,
                          ),
                        )
                      : (_isLoading
                            ? const Center(child: CircularProgressIndicator())
                            : Center(child: Text(lang.home_history_empty)))),
          );
        } else {
          return const Center(child: CircularProgressIndicator());
        }
      },
    );
  }
}

class TransactionItem extends StatelessWidget {
  final Transaction transaction;
  final Map<String, dynamic> settings;

  const TransactionItem({
    super.key,
    required this.transaction,
    required this.settings,
  });

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return Center(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(bottom: 8),
                  child: Text(
                    transaction.name,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
                if (transaction.type == TransactionType.transaction) ...[
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 16),
                    child: Text(
                      lang.home_history_transaction_on(
                        DateFormat(
                          'yyyy-MM-dd kk:mm',
                        ).format(transaction.createdAt),
                      ),
                      style: const TextStyle(color: Colors.grey),
                    ),
                  ),
                  TransactionProductsAmounts(
                    products: transaction.products!,
                    totalPrice: transaction.price,
                    settings: settings,
                  ),
                ],
                if (transaction.type == TransactionType.deposit) ...[
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 8),
                    child: Text(
                      lang.home_history_deposit_on(
                        DateFormat(
                          'yyyy-MM-dd kk:mm',
                        ).format(transaction.createdAt),
                      ),
                      style: const TextStyle(color: Colors.grey),
                    ),
                  ),
                  SizedBox(
                    width: double.infinity,
                    child: Text(
                      lang.home_history_amount(
                        '${settings['currency_symbol']} ${transaction.price.toStringAsFixed(2)}',
                      ),
                    ),
                  ),
                ],
                if (transaction.type == TransactionType.payment) ...[
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 8),
                    child: Text(
                      lang.home_history_payment_on(
                        DateFormat(
                          'yyyy-MM-dd kk:mm',
                        ).format(transaction.createdAt),
                      ),
                      style: const TextStyle(color: Colors.grey),
                    ),
                  ),
                  SizedBox(
                    width: double.infinity,
                    child: Text(
                      lang.home_history_amount(
                        '${settings['currency_symbol']} ${transaction.price.toStringAsFixed(2)}',
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
