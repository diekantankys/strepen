import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/post.dart';
import 'storage_service.dart';

class PostsService {
  static PostsService? _instance;

  Map<int, List<Post>> _posts = {};

  static PostsService getInstance() {
    _instance ??= PostsService();
    return _instance!;
  }

  void clearCache() {
    _posts = {};
  }

  void _ensureSuccess(http.Response response, Uri uri) {
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw http.ClientException(
        'Request failed (${response.statusCode}): ${response.body}',
        uri,
      );
    }
  }

  Post _postFromResponse(http.Response response) {
    final payload = json.decode(response.body);
    final data = payload is Map<String, dynamic> && payload['data'] != null
        ? payload['data']
        : payload;
    if (data is! Map<String, dynamic>) {
      throw const FormatException('Invalid post payload.');
    }
    return Post.fromJson(data);
  }

  Future<List<Post>> posts({int page = 1, bool forceReload = false}) async {
    if (!_posts.containsKey(page) || forceReload) {
      StorageService storage = await StorageService.getInstance();
      final uri = Uri.parse('${storage.organisation.apiUrl}/posts?page=$page');
      final response = await http.get(
        uri,
        headers: {
          'X-Api-Key': storage.organisation.apiKey,
          'Authorization': 'Bearer ${storage.token!}',
        },
      );
      _ensureSuccess(response, uri);

      final payload = json.decode(response.body);
      final postsJson = payload is Map<String, dynamic>
          ? payload['data']
          : payload;
      if (postsJson is! List) {
        throw const FormatException('Invalid posts payload.');
      }
      _posts[page] = postsJson.map<Post>((json) {
        if (json is! Map<String, dynamic>) {
          throw const FormatException('Invalid post item.');
        }
        return Post.fromJson(json);
      }).toList();
    }
    return _posts[page]!;
  }

  Future<Post> getPost({required int postId}) async {
    StorageService storage = await StorageService.getInstance();
    final uri = Uri.parse('${storage.organisation.apiUrl}/posts/$postId');
    final response = await http.get(
      uri,
      headers: {
        'X-Api-Key': storage.organisation.apiKey,
        'Authorization': 'Bearer ${storage.token!}',
      },
    );
    _ensureSuccess(response, uri);
    return _postFromResponse(response);
  }

  Future<Post> like({required int postId}) async {
    StorageService storage = await StorageService.getInstance();
    final response = await http.put(
      Uri.parse('${storage.organisation.apiUrl}/posts/$postId/like'),
      headers: {
        'X-Api-Key': storage.organisation.apiKey,
        'Authorization': 'Bearer ${storage.token!}',
      },
    );
    final uri = Uri.parse('${storage.organisation.apiUrl}/posts/$postId/like');
    _ensureSuccess(response, uri);
    return _postFromResponse(response);
  }

  Future<Post> dislike({required int postId}) async {
    StorageService storage = await StorageService.getInstance();
    final response = await http.put(
      Uri.parse('${storage.organisation.apiUrl}/posts/$postId/dislike'),
      headers: {
        'X-Api-Key': storage.organisation.apiKey,
        'Authorization': 'Bearer ${storage.token!}',
      },
    );
    final uri = Uri.parse(
      '${storage.organisation.apiUrl}/posts/$postId/dislike',
    );
    _ensureSuccess(response, uri);
    return _postFromResponse(response);
  }
}
