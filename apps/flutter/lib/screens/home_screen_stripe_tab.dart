import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../l10n/app_localizations.dart';
import '../models/product.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/settings_service.dart';
import '../services/product_service.dart';

class HomeScreenStripeTab extends StatefulWidget {
  const HomeScreenStripeTab({super.key});

  @override
  State createState() {
    return _HomeScreenStripeTabState();
  }
}

class _HomeScreenStripeTabState extends State {
  bool _forceReload = false;

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return FutureBuilder<List<dynamic>>(
      future: Future.wait([
        AuthService.getInstance().user(),
        SettingsService.getInstance().settings(),
        ProductsService.getInstance().activeProducts(forceReload: _forceReload),
      ]),
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          print('HomeScreenStripeTab error: ${snapshot.error}');
          return Center(child: Text(lang.home_stripe_products_error));
        } else if (snapshot.hasData) {
          User user = snapshot.data![0]!;
          List<Product> products = snapshot.data![2]!;
          if (user.minor!) {
            products = products
                .where((Product product) => !product.alcoholic)
                .toList();
          }
          return RefreshIndicator(
            onRefresh: () async {
              setState(() => _forceReload = true);
            },
            child: ProductsList(
              user: user,
              settings: snapshot.data![1]!,
              products: products,
            ),
          );
        } else {
          return const Center(child: CircularProgressIndicator());
        }
      },
    );
  }
}

class ProductsList extends StatefulWidget {
  final User user;
  final Map<String, dynamic> settings;
  final List<Product> products;

  const ProductsList({
    super.key,
    required this.user,
    required this.settings,
    required this.products,
  });

  @override
  State createState() {
    return _ProductsListState();
  }
}

class _ProductsListState extends State<ProductsList> {
  final ScrollController _scrollController = ScrollController();

  final List<int> _amounts = [];

  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    for (int i = 0; i < widget.products.length; i++) {
      _amounts.add(0);
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> createTransaction() async {
    final lang = AppLocalizations.of(context)!;

    final Map<Product, int> productAmounts = {};
    int index = 0;
    for (Product product in widget.products) {
      if (_amounts[index] > 0) {
        productAmounts[product] = _amounts[index];
      }
      index++;
    }

    if (productAmounts.isNotEmpty) {
      setState(() => _isLoading = true);

      if (await AuthService.getInstance().createTransaction(
        productAmounts: productAmounts,
      )) {
        if (!mounted) return;
        setState(() {
          for (int i = 0; i < widget.products.length; i++) {
            _amounts[i] = 0;
          }
        });

        showDialog(
          context: context,
          builder: (BuildContext context) {
            return TransactionCreatedDialog(
              user: widget.user,
              settings: widget.settings,
              productAmounts: productAmounts,
            );
          },
        );
      } else {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(lang.home_stripe_create_error),
            action: SnackBarAction(
              label: lang.home_stripe_close,
              onPressed: () {},
            ),
          ),
        );
      }

      if (!mounted) return;
      _scrollController.animateTo(
        0,
        duration: const Duration(milliseconds: 300),
        curve: Curves.ease,
      );
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return Center(
      child: SingleChildScrollView(
        controller: _scrollController,
        child: Column(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 8,
                  vertical: 12,
                ),
                child: ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: widget.products.length,
                  itemBuilder: (context, index) {
                    Product product = widget.products[index];
                    int amount = _amounts[index];
                    return Container(
                      margin: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      child: Row(
                        children: [
                          Container(
                            margin: const EdgeInsets.only(right: 16),
                            child: SizedBox(
                              width: 56,
                              height: 56,
                              child: Card(
                                clipBehavior: Clip.antiAliasWithSaveLayer,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                elevation: 2,
                                child: Container(
                                  decoration: BoxDecoration(
                                    image: DecorationImage(
                                      fit: BoxFit.cover,
                                      image: CachedNetworkImageProvider(
                                        product.image,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                          Expanded(
                            flex: 1,
                            child: Column(
                              children: [
                                Container(
                                  margin: const EdgeInsets.only(bottom: 4),
                                  child: SizedBox(
                                    width: double.infinity,
                                    child: Text(
                                      product.name,
                                      style: const TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                ),
                                SizedBox(
                                  width: double.infinity,
                                  child: Text(
                                    '${widget.settings['currency_symbol']} ${product.price.toStringAsFixed(2)}',
                                    style: const TextStyle(color: Colors.grey),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                onPressed: () {
                                  setState(() {
                                    if (_amounts[index] > 0) {
                                      _amounts[index]--;
                                    }
                                  });
                                },
                                icon: const Icon(Icons.remove),
                                tooltip: lang.home_stripe_decrement,
                              ),
                              Container(
                                margin: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                ),
                                child: SizedBox(
                                  width: 16,
                                  child: Text(
                                    amount.toString(),
                                    style: const TextStyle(
                                      fontSize: 20,
                                      fontWeight: FontWeight.w500,
                                    ),
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                              ),
                              IconButton(
                                onPressed: () {
                                  setState(() {
                                    if (_amounts[index] <
                                        widget.settings['max_stripe_amount']) {
                                      _amounts[index]++;
                                    }
                                  });
                                },
                                icon: const Icon(Icons.add),
                                tooltip: lang.home_stripe_increment,
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
              if (widget.user.minor!) ...[
                Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  child: Text(
                    lang.home_stripe_minor,
                    style: const TextStyle(
                      fontSize: 16,
                      color: Colors.grey,
                      fontStyle: FontStyle.italic,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ),
              ],
              Container(
                margin: const EdgeInsets.only(left: 16, right: 16, bottom: 16),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : createTransaction,
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
                      lang.home_stripe_stripe,
                      style: const TextStyle(color: Colors.white, fontSize: 18),
                    ),
                  ),
                ),
              ),
            ],
          ),
      ),
    );
  }
}

class TransactionCreatedDialog extends StatelessWidget {
  final User user;

  final Map<String, dynamic> settings;

  final Map<Product, int> productAmounts;

  const TransactionCreatedDialog({
    super.key,
    required this.user,
    required this.settings,
    required this.productAmounts,
  });

  @override
  Widget build(BuildContext context) {
    final lang = AppLocalizations.of(context)!;
    return AlertDialog(
      title: Text(lang.home_stripe_created),
      content: SizedBox(
        width: MediaQuery.of(context).size.width * 0.9,
        height: MediaQuery.of(context).size.height * 0.9,
        child: Center(
          child: SingleChildScrollView(
            child: Column(
              children: [
                Container(
                  margin: const EdgeInsets.only(bottom: 24),
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
                Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: Text(
                    lang.home_stripe_thanks(user.firstname),
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
                Container(
                  margin: const EdgeInsets.only(bottom: 24),
                  child: Text(
                    lang.home_stripe_new_balance(
                      '${settings['currency_symbol']} ${user.balance!.toStringAsFixed(2)}',
                    ),
                    style: const TextStyle(fontSize: 16),
                  ),
                ),
                TransactionProductsAmounts(
                  products: productAmounts,
                  totalPrice: null,
                  settings: settings,
                ),
                Container(
                  margin: const EdgeInsets.only(top: 8),
                  child: SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context),
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
                        lang.home_stripe_close,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class TransactionProductsAmounts extends StatelessWidget {
  final Map<Product, int> products;

  final double? totalPrice;

  final Map<String, dynamic> settings;

  const TransactionProductsAmounts({
    super.key,
    required this.products,
    required this.totalPrice,
    required this.settings,
  });

  @override
  Widget build(BuildContext context) {
    int totalAmount = 0;
    double realTotalPrice = 0;
    for (Product product in products.keys) {
      int amount = products[product]!;
      totalAmount += amount;
      realTotalPrice += amount * product.price;
    }

    return Column(
      children: [
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: products.length,
          itemBuilder: (context, index) {
            Product product = products.keys.elementAt(index);
            int amount = products[product]!;
            return Container(
              margin: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  Container(
                    margin: const EdgeInsets.only(right: 16),
                    child: SizedBox(
                      width: 56,
                      height: 56,
                      child: Card(
                        clipBehavior: Clip.antiAliasWithSaveLayer,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(6),
                        ),
                        elevation: 2,
                        child: Container(
                          decoration: BoxDecoration(
                            image: DecorationImage(
                              fit: BoxFit.cover,
                              image: CachedNetworkImageProvider(product.image),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                  Expanded(
                    flex: 1,
                    child: Column(
                      children: [
                        Container(
                          margin: const EdgeInsets.only(bottom: 4),
                          child: SizedBox(
                            width: double.infinity,
                            child: Text(
                              product.name,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                        ),
                        SizedBox(
                          width: double.infinity,
                          child: Text(
                            '${amount}x    ${settings['currency_symbol']} ${totalPrice != null && totalPrice != realTotalPrice ? '?' : product.price.toStringAsFixed(2)}',
                            style: const TextStyle(color: Colors.grey),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Text(
                    '${settings['currency_symbol']} ${totalPrice != null && totalPrice != realTotalPrice ? '?' : (product.price * amount).toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            );
          },
        ),
        const Divider(),
        Row(
          children: [
            Container(
              margin: const EdgeInsets.only(right: 16),
              child: const SizedBox(width: 56, height: 56),
            ),
            Expanded(
              flex: 1,
              child: Text(
                '${totalAmount}x',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            Text(
              '${settings['currency_symbol']} ${(totalPrice ?? realTotalPrice).toStringAsFixed(2)}',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w500),
            ),
          ],
        ),
      ],
    );
  }
}
