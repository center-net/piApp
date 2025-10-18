import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:webview_flutter/webview_flutter.dart';

class PiAuthService {
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();
  static const String _baseUrl = 'https://piapp-main-4xd19j.laravel.cloud';

  // حفظ التوكن في التخزين الآمن
  Future<void> saveToken(String token, Map<String, dynamic> user) async {
    await _secureStorage.write(key: 'pi_token', value: token);
    await _secureStorage.write(key: 'pi_user', value: json.encode(user));
  }

  // حذف التوكن
  Future<void> clearToken() async {
    await _secureStorage.delete(key: 'pi_token');
    await _secureStorage.delete(key: 'pi_user');
  }

  // الحصول على التوكن الحالي
  Future<String?> getToken() async {
    return await _secureStorage.read(key: 'pi_token');
  }

  // الحصول على بيانات المستخدم الحالية
  Future<Map<String, dynamic>?> getUser() async {
    final userString = await _secureStorage.read(key: 'pi_user');
    if (userString != null) {
      return json.decode(userString) as Map<String, dynamic>;
    }
    return null;
  }

  // بدء عملية تسجيل الدخول مع Pi
  void startPiLogin(WebViewController controller) {
    controller.loadRequest(Uri.parse('$_baseUrl/pi/auth'));
  }

  // معالجة رد الاتصال بعد تسجيل الدخول
  Future<bool> handlePiCallback(Uri uri) async {
    // التحقق من وجود التوكن في رد الاتصال
    final token = uri.fragment.split('=').last;

    if (token.isNotEmpty) {
      try {
        // استخدام التوكن للحصول على بيانات المستخدم
        final response = await http.get(
          Uri.parse('$_baseUrl/api/me'),
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json',
          },
        );

        if (response.statusCode == 200) {
          final user = json.decode(response.body);
          await saveToken(token, user);
          return true;
        }
      } catch (e) {
        print('Error handling Pi callback: $e');
      }
    }
    return false;
  }
}
