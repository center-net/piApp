import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:provider/provider.dart';
import 'package:frontend/services/pi_auth_service.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with WidgetsBindingObserver {
  bool isLoading = false;
  String? errorMessage;
  final String loginUrl = 'https://piapp-main-4xd19j.laravel.cloud/pi-login';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _initializeApp();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// تهيئة التطبيق والتحقق من الجلسة الموجودة
  Future<void> _initializeApp() async {
    if (!mounted) return;

    final authService = Provider.of<PiAuthService>(context, listen: false);
    final isLoggedIn = await authService.isLoggedIn();

    if (mounted && isLoggedIn) {
      // انتقل إلى الصفحة الرئيسية
      Navigator.pushReplacementNamed(context, '/home');
    }
  }

  /// فتح صفحة تسجيل الدخول
  Future<void> _loginWithPi() async {
    if (isLoading) return;

    setState(() {
      isLoading = true;
      errorMessage = null;
    });

    try {
      final uri = Uri.parse(loginUrl);

      // التحقق من أن URL صحيحة
      if (!uri.isAbsolute) {
        throw Exception('URL غير صحيحة');
      }

      // فتح الـ URL في المتصفح
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
        throw Exception('لا يمكن فتح صفحة تسجيل الدخول');
      }

      // انتظر قليلاً حتى يكمل المستخدم التسجيل
      _startTokenCheckTimer();
    } catch (e) {
      if (mounted) {
        setState(() {
          isLoading = false;
          errorMessage = _getErrorMessage(e);
        });
        _showErrorDialog(errorMessage!);
      }
    }
  }

  /// بدء مؤقت للتحقق من التوكن
  void _startTokenCheckTimer() {
    Future.delayed(const Duration(seconds: 3), () async {
      if (!mounted) return;

      final authService = Provider.of<PiAuthService>(context, listen: false);

      for (int i = 0; i < 10; i++) {
        final token = await authService.getToken();
        if (token != null) {
          // تم الحصول على التوكن، تحقق من صلاحيته
          final isValid = await authService.verifyToken(token);
          if (mounted && isValid) {
            Navigator.pushReplacementNamed(context, '/home');
            return;
          }
        }

        await Future.delayed(const Duration(seconds: 1));
      }

      if (mounted) {
        setState(() {
          isLoading = false;
        });
        _showErrorDialog('لم تكتمل عملية تسجيل الدخول');
      }
    });
  }

  /// الحصول على رسالة الخطأ
  String _getErrorMessage(dynamic error) {
    if (error is Exception) {
      return error.toString().replaceAll('Exception: ', '');
    }
    return 'حدث خطأ غير متوقع';
  }

  /// عرض رسالة خطأ
  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('⚠️ خطأ'),
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

  /// عند العودة من المتصفح
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && isLoading) {
      // تحقق من التوكن عند العودة للتطبيق
      _checkTokenAfterResume();
    }
  }

  /// التحقق من التوكن بعد العودة من المتصفح
  Future<void> _checkTokenAfterResume() async {
    final authService = Provider.of<PiAuthService>(context, listen: false);
    final token = await authService.getToken();

    if (token != null) {
      final isValid = await authService.verifyToken(token);
      if (mounted && isValid) {
        Navigator.pushReplacementNamed(context, '/home');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('🔐 تسجيل الدخول'),
        centerTitle: true,
        elevation: 0,
        backgroundColor: const Color(0xFF667eea),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF667eea), Color(0xFF764ba2)],
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // الأيقونة
                Container(
                  width: 100,
                  height: 100,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.1),
                        blurRadius: 20,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.verified_user,
                    size: 60,
                    color: Color(0xFF667eea),
                  ),
                ),
                const SizedBox(height: 30),

                // العنوان
                const Text(
                  'مرحباً بك',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 10),

                // الوصف
                const Text(
                  'سجل الدخول باستخدام حساب Pi الخاص بك',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 16, color: Colors.white70),
                ),
                const SizedBox(height: 40),

                // رسالة الخطأ
                if (errorMessage != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.red.withOpacity(0.2),
                      border: Border.all(color: Colors.red, width: 1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.error_outline, color: Colors.red),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            errorMessage!,
                            style: const TextStyle(color: Colors.red),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                ],

                // زر تسجيل الدخول
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton.icon(
                    onPressed: isLoading ? null : _loginWithPi,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      disabledBackgroundColor: Colors.white38,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      elevation: 0,
                    ),
                    icon: isLoading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: AlwaysStoppedAnimation<Color>(
                                Color(0xFF667eea),
                              ),
                            ),
                          )
                        : const Icon(Icons.login, color: Color(0xFF667eea)),
                    label: Text(
                      isLoading ? 'جاري التسجيل...' : '🔓 تسجيل الدخول مع Pi',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF667eea),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                // رسالة معلوماتية
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    border: Border.all(color: Colors.white30, width: 1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.info_outline, color: Colors.white, size: 20),
                      SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'سيتم فتح متصفح الويب لتسجيل الدخول بأمان',
                          style: TextStyle(color: Colors.white70, fontSize: 12),
                        ),
                      ),
                    ],
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
