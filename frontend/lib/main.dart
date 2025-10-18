import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:frontend/services/pi_auth_service.dart';
import 'package:frontend/routes/app_routes.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (context) => PiAuthService(),
      child: MaterialApp(
        title: 'تطبيق Pi',
        theme: ThemeData(
          primarySwatch: Colors.blue,
          visualDensity: VisualDensity.adaptivePlatformDensity,
          fontFamily: 'Tajawal',
          textTheme: const TextTheme(
            headline1: TextStyle(fontSize: 24.0, fontWeight: FontWeight.bold),
            headline6: TextStyle(fontSize: 18.0, fontWeight: FontWeight.bold),
            bodyText2: TextStyle(fontSize: 14.0),
          ),
        ),
        initialRoute: AppRoutes.login,
        routes: {
          AppRoutes.login: (context) => const LoginScreen(),
          AppRoutes.home: (context) => const HomeScreen(),
        },
        onUnknownRoute: AppRoutes.generateRoute,
      ),
    );
  }
}

class PiLoginScreen extends StatefulWidget {
  const PiLoginScreen({super.key});

  @override
  State<PiLoginScreen> createState() => _PiLoginScreenState();
}

class _PiLoginScreenState extends State<PiLoginScreen> {
  final _controller = Completer<WebViewController>();
  final storage = const FlutterSecureStorage();

  // رابط صفحة المصادقة في Laravel
  final String loginUrl = 'https://piapp-main-4xd19j.laravel.cloud/pi/auth';
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _checkExistingSession();
  }

  Future<void> _checkExistingSession() async {
    final piAuthService = Provider.of<PiAuthService>(context, listen: false);
    final isLoggedIn = await piAuthService.isLoggedIn();

    if (isLoggedIn && mounted) {
      Navigator.pushReplacementNamed(context, AppRoutes.home);
    } else {
      setState(() {
        isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تسجيل الدخول مع Pi')),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : WebView(
              initialUrl: loginUrl,
              javascriptMode: JavascriptMode.unrestricted,
              onWebViewCreated: (ctrl) => _controller.complete(ctrl),
              navigationDelegate: (navRequest) {
                final uri = Uri.parse(navRequest.url);
                // نتحقق من وجود token في fragment (بعد #)
                if (uri.scheme == 'https' && uri.host == 'callback.local') {
                  final fragment = uri.fragment; // e.g. token=...
                  if (fragment.startsWith('token=')) {
                    final token = fragment.split('token=')[1];
                    _onTokenReceived(token);
                  }
                  return NavigationDecision.prevent;
                }
                return NavigationDecision.navigate;
              },
            ),
    );
  }

  Future<void> _onTokenReceived(String token) async {
    // خزّن التوكن بشكل آمن
    await storage.write(key: 'api_token', value: token);

    // اختبار: استدعاء endpoint /api/me
    final res = await http.get(
      Uri.parse('https://piapp-main-4xd19j.laravel.cloud/api/me'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (res.statusCode == 200) {
      final user = json.decode(res.body);
      // انتقل لصفحة رئيسية
      if (!mounted) return;
      Navigator.pushReplacementNamed(context, AppRoutes.home);
    } else {
      // فشل: أظهر رسالة خطأ
      if (!mounted) return;
      showDialog(context: context, builder: (_) => AlertDialog(
        title: const Text('فشل المصادقة'),
        content: Text(res.body),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('موافق'),
          ),
        ],
      ));
    }
  }
}
