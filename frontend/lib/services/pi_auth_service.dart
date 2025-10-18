import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class PiAuthService extends ChangeNotifier {
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();
  static const String _baseUrl = 'https://piapp-main-4xd19j.laravel.cloud';
  static const Duration _timeout = Duration(seconds: 30);

  bool _isLoggedIn = false;
  Map<String, dynamic>? _user;
  String? _error;

  // Getters
  bool get isLoggedIn => _isLoggedIn;
  Map<String, dynamic>? get user => _user;
  String? get error => _error;
  String? get displayName =>
      _user?['name'] ?? _user?['pi_username'] ?? 'مستخدم';

  /// حفظ التوكن وبيانات المستخدم في التخزين الآمن
  Future<bool> saveToken(String token, Map<String, dynamic> user) async {
    try {
      await _secureStorage.write(key: 'api_token', value: token);
      await _secureStorage.write(key: 'pi_user', value: jsonEncode(user));
      _user = user;
      _isLoggedIn = true;
      _error = null;
      notifyListeners();
      return true;
    } catch (e) {
      _error = 'فشل حفظ البيانات: $e';
      notifyListeners();
      return false;
    }
  }

  /// حذف التوكن وبيانات المستخدم
  Future<bool> clearToken() async {
    try {
      await _secureStorage.delete(key: 'api_token');
      await _secureStorage.delete(key: 'pi_user');
      _user = null;
      _isLoggedIn = false;
      _error = null;
      notifyListeners();
      return true;
    } catch (e) {
      _error = 'فشل حذف البيانات: $e';
      notifyListeners();
      return false;
    }
  }

  /// الحصول على التوكن الحالي
  Future<String?> getToken() async {
    try {
      return await _secureStorage.read(key: 'api_token');
    } catch (e) {
      debugPrint('❌ خطأ في قراءة التوكن: $e');
      return null;
    }
  }

  /// الحصول على بيانات المستخدم الحالية
  Future<Map<String, dynamic>?> getUser() async {
    try {
      final userString = await _secureStorage.read(key: 'pi_user');
      if (userString != null) {
        _user = jsonDecode(userString) as Map<String, dynamic>;
        return _user;
      }
      return null;
    } catch (e) {
      debugPrint('❌ خطأ في قراءة بيانات المستخدم: $e');
      return null;
    }
  }

  /// التحقق من وجود جلسة مستخدم صالحة
  Future<bool> isLoggedIn() async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) {
        _isLoggedIn = false;
        notifyListeners();
        return false;
      }

      // التحقق من صلاحية التوكن
      final isValid = await verifyToken(token);
      _isLoggedIn = isValid;
      notifyListeners();
      return isValid;
    } catch (e) {
      debugPrint('❌ خطأ في التحقق من الجلسة: $e');
      await clearToken();
      return false;
    }
  }

  /// التحقق من صلاحية التوكن
  Future<bool> verifyToken(String token) async {
    try {
      final response = await http
          .get(
            Uri.parse('$_baseUrl/api/me'),
            headers: {
              'Authorization': 'Bearer $token',
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          )
          .timeout(_timeout);

      if (response.statusCode == 200) {
        final userData = jsonDecode(response.body);
        if (userData is Map) {
          final user = userData['user'] as Map<String, dynamic>?;
          if (user != null) {
            _user = user;
            _isLoggedIn = true;
            _error = null;
            notifyListeners();
            return true;
          }
        }
      } else if (response.statusCode == 401 || response.statusCode == 403) {
        // التوكن غير صالح
        await clearToken();
        _error = 'انتهت صلاحية الجلسة';
        return false;
      }

      return false;
    } on http.ClientException catch (e) {
      _error = 'خطأ في الاتصال: ${e.message}';
      notifyListeners();
      return false;
    } on TimeoutException catch (_) {
      _error = 'انتهت مهلة الاتصال';
      notifyListeners();
      return false;
    } catch (e) {
      debugPrint('❌ خطأ في التحقق من التوكن: $e');
      _error = 'خطأ في التحقق: $e';
      notifyListeners();
      return false;
    }
  }

  /// تحديث بيانات المستخدم من الخادم
  Future<bool> refreshUserData() async {
    try {
      final token = await getToken();
      if (token == null) {
        _error = 'لا يوجد توكن';
        notifyListeners();
        return false;
      }

      final response = await http
          .get(
            Uri.parse('$_baseUrl/api/me'),
            headers: {
              'Authorization': 'Bearer $token',
              'Accept': 'application/json',
            },
          )
          .timeout(_timeout);

      if (response.statusCode == 200) {
        final userData = jsonDecode(response.body);
        final user = userData['user'] as Map<String, dynamic>?;
        if (user != null) {
          await saveToken(token, user);
          _error = null;
          return true;
        }
      } else if (response.statusCode == 401) {
        await clearToken();
        _error = 'انتهت صلاحية الجلسة';
      } else {
        _error = 'فشل تحديث البيانات';
      }

      notifyListeners();
      return false;
    } on TimeoutException catch (_) {
      _error = 'انتهت مهلة الاتصال';
      notifyListeners();
      return false;
    } catch (e) {
      _error = 'خطأ: $e';
      notifyListeners();
      return false;
    }
  }

  /// تسجيل الخروج
  Future<bool> logout() async {
    try {
      final token = await getToken();
      if (token != null) {
        // محاولة تسجيل الخروج من الخادم
        await http
            .post(
              Uri.parse('$_baseUrl/api/logout'),
              headers: {
                'Authorization': 'Bearer $token',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
              },
            )
            .timeout(const Duration(seconds: 10))
            .catchError((_) => null); // تجاهل أخطاء الاتصال
      }

      // حذف البيانات المحلية بغض النظر عن نتيجة الخادم
      return await clearToken();
    } catch (e) {
      debugPrint('❌ خطأ في تسجيل الخروج: $e');
      await clearToken();
      return true;
    }
  }

  /// محاولة جديدة للاتصال (Retry Logic)
  Future<http.Response> fetchWithRetry(
    String url, {
    int maxRetries = 3,
    bool useAuth = true,
  }) async {
    String? token;
    if (useAuth) {
      token = await getToken();
    }

    http.Response? lastResponse;
    Exception? lastException;

    for (int i = 0; i < maxRetries; i++) {
      try {
        final headers = {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        };

        if (token != null) {
          headers['Authorization'] = 'Bearer $token';
        }

        lastResponse = await http
            .get(Uri.parse(url), headers: headers)
            .timeout(_timeout);

        if (lastResponse.statusCode == 200) {
          return lastResponse;
        }

        // إذا كان الخطأ 401 أو 403، لا تعيد المحاولة
        if (lastResponse.statusCode == 401 || lastResponse.statusCode == 403) {
          return lastResponse;
        }

        // انتظر قليلاً قبل إعادة المحاولة
        await Future.delayed(Duration(seconds: i + 1));
      } catch (e) {
        lastException = e as Exception;
        if (i < maxRetries - 1) {
          await Future.delayed(Duration(seconds: i + 1));
        }
      }
    }

    // إذا فشلت جميع المحاولات
    if (lastResponse != null) {
      return lastResponse;
    }
    throw lastException ?? Exception('فشل الاتصال بعد عدة محاولات');
  }
}
