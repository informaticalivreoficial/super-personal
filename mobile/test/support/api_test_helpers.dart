import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:super_personal/core/api/api_client.dart';
import 'package:super_personal/core/storage/token_storage.dart';

/// Adapter fake: responde com o JSON devolvido pelo handler, sem rede.
class FakeHttpAdapter implements HttpClientAdapter {
  FakeHttpAdapter(this.handler);

  final Future<ResponseBody> Function(RequestOptions options) handler;

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) {
    return handler(options);
  }

  @override
  void close({bool force = false}) {}
}

/// `ApiClient` com o adapter fake em `https://api.test/api/v1`.
ApiClient clientWith(
  Future<ResponseBody> Function(RequestOptions options) handler,
) {
  final client = ApiClient(
    tokenStorage: InMemoryTokenStorage(),
    baseUrl: 'https://api.test/api/v1',
  );
  client.dio.httpClientAdapter = FakeHttpAdapter(handler);

  return client;
}

/// `ResponseBody` com JSON e o status informado.
ResponseBody jsonResponse(Object body, int status) {
  return ResponseBody.fromString(
    jsonEncode(body),
    status,
    headers: {
      Headers.contentTypeHeader: [Headers.jsonContentType],
    },
  );
}
