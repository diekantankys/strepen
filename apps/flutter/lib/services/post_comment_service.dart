import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/post_comment.dart';
import 'storage_service.dart';

class PostCommentsService {
  static PostCommentsService? _instance;

  static PostCommentsService getInstance() {
    _instance ??= PostCommentsService();
    return _instance!;
  }

  Future<Map<String, String>> _headers() async {
    final storage = await StorageService.getInstance();
    return {
      'X-Api-Key': storage.organisation.apiKey,
      'Authorization': 'Bearer ${storage.token!}',
    };
  }

  void _ensureSuccess(http.Response response, Uri uri) {
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw http.ClientException(
        'Request failed (${response.statusCode}): ${response.body}',
        uri,
      );
    }
  }

  PostComment _commentFromResponse(http.Response response) {
    final payload = json.decode(response.body);
    final data = payload is Map<String, dynamic> && payload['data'] != null
        ? payload['data']
        : payload;
    if (data is! Map<String, dynamic>) {
      throw const FormatException('Invalid comment payload.');
    }
    return PostComment.fromJson(data);
  }

  Future<List<PostComment>> comments({required int postId}) async {
    final storage = await StorageService.getInstance();
    final uri = Uri.parse(
      '${storage.organisation.apiUrl}/posts/$postId/comments',
    );
    final response = await http.get(uri, headers: await _headers());
    _ensureSuccess(response, uri);

    final responseJson = json.decode(response.body);
    // This API disables Laravel's default resource wrapper, while other
    // installations may keep it enabled. Support both response shapes.
    final data = responseJson is Map<String, dynamic>
        ? responseJson['data']
        : responseJson;
    if (data is! List) {
      throw const FormatException('Invalid comments payload.');
    }

    return data.map<PostComment>((commentJson) {
      if (commentJson is! Map<String, dynamic>) {
        throw const FormatException('Invalid comment item.');
      }
      return PostComment.fromJson(commentJson);
    }).toList();
  }

  Future<PostComment> createComment({
    required int postId,
    required String body,
    int? parentId,
  }) async {
    final storage = await StorageService.getInstance();
    final uri = Uri.parse(
      '${storage.organisation.apiUrl}/posts/$postId/comments',
    );
    final response = await http.post(
      uri,
      headers: await _headers(),
      body: {
        'body': body,
        if (parentId != null) 'parent_id': parentId.toString(),
      },
    );
    _ensureSuccess(response, uri);
    return _commentFromResponse(response);
  }

  Future<PostComment> likeComment({
    required int postId,
    required int commentId,
  }) async {
    final storage = await StorageService.getInstance();
    final uri = Uri.parse(
      '${storage.organisation.apiUrl}/posts/$postId/comments/$commentId/like',
    );
    final response = await http.put(uri, headers: await _headers());
    _ensureSuccess(response, uri);
    return _commentFromResponse(response);
  }

  Future<PostComment> dislikeComment({
    required int postId,
    required int commentId,
  }) async {
    final storage = await StorageService.getInstance();
    final uri = Uri.parse(
      '${storage.organisation.apiUrl}/posts/$postId/comments/$commentId/dislike',
    );
    final response = await http.put(uri, headers: await _headers());
    _ensureSuccess(response, uri);
    return _commentFromResponse(response);
  }

  Future<void> deleteComment({
    required int postId,
    required int commentId,
  }) async {
    final storage = await StorageService.getInstance();
    final uri = Uri.parse(
      '${storage.organisation.apiUrl}/posts/$postId/comments/$commentId',
    );
    final response = await http.delete(uri, headers: await _headers());
    _ensureSuccess(response, uri);
  }
}
