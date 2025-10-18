import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:frontend/services/pi_auth_service.dart';

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
        title: 'Pi Flutter WebView',
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
        home: const PiLoginScreen(),
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login with Pi')),
      body: WebView(
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
      // انتقل لصفحة رئيسية أو أظهر المستخدم
      if (!mounted) return;
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => HomePage(user: user)));
    } else {
      // فشل: أظهر رسالة خطأ
      if (!mounted) return;
      showDialog(context: context, builder: (_) => AlertDialog(title: const Text('Auth failed'), content: Text(res.body)));
    }
  }
}

class HomePage extends StatelessWidget {
  final Map<String, dynamic> user;
  const HomePage({super.key, required this.user});

  @override
  Widget build(BuildContext context) {
    final piAuthService = Provider.of<PiAuthService>(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Home'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await piAuthService.clearToken();
              if (!mounted) return;
              Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const PiLoginScreen()));
            },
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              'Welcome, ${user['pi_username'] ?? user['email'] ?? 'Pioneer'}',
              style: const TextStyle(fontSize: 24),
            ),
            const SizedBox(height: 20),
            Text(
              'Email: ${user['email'] ?? 'N/A'}',
              style: const TextStyle(fontSize: 16),
            ),
            const SizedBox(height: 10),
            Text(
              'Username: ${user['pi_username'] ?? 'N/A'}',
              style: const TextStyle(fontSize: 16),
            ),
          ],
        ),
      ),
    );
  }
}
