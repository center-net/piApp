import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class PiAuthService extends ChangeNotifier {
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();
  bool _isAuthenticated = false;
  String? _userId;
  String? _username;
  String? _accessToken;

  bool get isAuthenticated => _isAuthenticated;
  String? get userId => _userId;
  String? get username => _username;

  PiAuthService() {
    _checkAuthentication();
  }

  Future<void> _checkAuthentication() async {
    final token = await _secureStorage.read(key: 'pi_token');
    if (token != null) {
      _accessToken = token;
      final user = await _secureStorage.read(key: 'pi_user');
      if (user != null) {
        final userData = jsonDecode(user);
        _userId = userData['id'];
        _username = userData['username'];
        _isAuthenticated = true;
      }
    }
    notifyListeners();
  }

  Future<void> authenticateWithPi() async {
    final Uri piDev = Uri.parse('pi://develop.pinet.com');

    try {
      if (await canLaunchUrl(piDev)) {
        await launchUrl(piDev);
      } else {
        // فتح كنسخة ويب عادية إذا لم تتوفر الـ scheme
        await launchUrl(Uri.parse('https://develop.pinet.com'));
      }

      // في تطبيق حقيقي، هنا يجب أن تستمع إلى رد من Pi
      // وتستقبل التوكين ثم تستدعي API الخاص بك للتسجيل
      // هذا مجرد مثال مبسط
      await Future.delayed(const Duration(seconds: 3));

      // في التطبيق الفعلي، ستحصل على التوكن من رد Pi
      // ثم تستدخدمه للتحقق من الخادم وتخزين بيانات المستخدم
      _simulateAuthentication();
    } catch (e) {
      debugPrint('خطأ في المصادقة مع Pi: $e');
    }
  }

  void _simulateAuthentication() {
    // محاكاة للمصادقة - في التطبيق الحقيقي ستحصل على هذه البيانات من API
    _userId = '12345';
    _username = 'pi_user';
    _accessToken = 'sample_access_token';
    _isAuthenticated = true;

    // تخزين البيانات
    _secureStorage.write(key: 'pi_token', value: _accessToken);
    _secureStorage.write(
      key: 'pi_user', 
      value: jsonEncode({
        'id': _userId,
        'username': _username,
      })
    );

    notifyListeners();
  }

  Future<void> createPayment(String to, double amount, {String? memo}) async {
    if (!_isAuthenticated || _accessToken == null) {
      throw Exception('المستخدم غير مسجل الدخول');
    }

    final url = Uri.parse('http://localhost:8000/api/create-payment');

    try {
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $_accessToken',
        },
        body: jsonEncode({
          'to': to,
          'amount': amount,
          'memo': memo ?? 'Payment from Pi App',
        }),
      );

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        return responseData;
      } else {
        throw Exception('فشل إنشاء الدفعة: ${response.body}');
      }
    } catch (e) {
      throw Exception('خطأ في إنشاء الدفعة: $e');
    }
  }

  Future<void> logout() async {
    _isAuthenticated = false;
    _userId = null;
    _username = null;
    _accessToken = null;

    await _secureStorage.delete(key: 'pi_token');
    await _secureStorage.delete(key: 'pi_user');

    notifyListeners();
  }
}
