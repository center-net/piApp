import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:provider/provider.dart';
import 'package:frontend/services/pi_auth_service.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final storage = const FlutterSecureStorage();
  bool isLoading = false;
  final String loginUrl = 'https://piapp-main-4xd19j.laravel.cloud/pi/auth';
  final String callbackUrl = 'https://callback.local/#token=';

  @override
  void initState() {
    super.initState();
    _checkExistingSession();
  }

  Future<void> _checkExistingSession() async {
    final piAuthService = Provider.of<PiAuthService>(context, listen: false);
    final isLoggedIn = await piAuthService.isLoggedIn();

    if (isLoggedIn && mounted) {
      Navigator.pushReplacementNamed(context, '/home');
    }
  }

  Future<void> _loginWithPi() async {
    setState(() {
      isLoading = true;
    });

    try {
      // فتح متصفح Pi Browser أو WebView لتسجيل الدخول
      final uri = Uri.parse(loginUrl);
      if (!await launchUrl(
        uri,
        mode: LaunchMode.externalApplication,
      )) {
        throw 'Could not launch $uri';
      }
    } catch (e) {
      setState(() {
        isLoading = false;
      });

      if (mounted) {
        _showErrorDialog('فشل فتح صفحة تسجيل الدخول: $e');
      }
    }
  }

  // هذه الطريقة تستخدم للاستماع إلى تغييرات الرابط في WebView
  // في التطبيق الفعلي، قد تحتاج إلى استخدام WebView لمراقبة رد الاتصال
  Future<void> _checkForToken() async {
    // في التطبيق الفعلي، ستقوم بمراقبة رد الاتصال هنا
    // مثال: قد تحتاج إلى استخدام JavaScript channels أو NavigationDelegate
    // لالتقاط رد الاتصال من Pi SDK

    // مؤقتاً، نتحقق من التخزين بعد فترة قصيرة
    Future.delayed(const Duration(seconds: 5), () async {
      final token = await storage.read(key: 'api_token');
      if (token != null && mounted) {
        Navigator.pushReplacementNamed(context, '/home');
      }
    });
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('خطأ'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('موافق'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('تسجيل الدخول'),
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.pi,
                size: 80,
                color: Colors.blue,
              ),
              const SizedBox(height: 20),
              const Text(
                'مرحباً بك في تطبيق Pi',
                style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 10),
              const Text(
                'سجل الدخول باستخدام حساب Pi الخاص بك',
                style: TextStyle(fontSize: 16),
              ),
              const SizedBox(height: 40),
              isLoading
                  ? const CircularProgressIndicator()
                  : ElevatedButton.icon(
                      onPressed: _loginWithPi,
                      icon: const Icon(Icons.login),
                      label: const Text('تسجيل الدخول مع Pi'),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 40,
                          vertical: 16,
                        ),
                        textStyle: const TextStyle(fontSize: 18),
                      ),
                    ),
              const SizedBox(height: 20),
              TextButton(
                onPressed: () {
                  // يمكن إضافة مساعدة هنا
                },
                child: const Text('مساعدة في تسجيل الدخول'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
