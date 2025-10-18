import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:provider/provider.dart';
import 'package:frontend/services/pi_auth_service.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final storage = const FlutterSecureStorage();
  Map<String, dynamic>? user;
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    try {
      final token = await storage.read(key: 'api_token');
      if (token != null) {
        final response = await http.get(
          Uri.parse('https://piapp-main-4xd19j.laravel.cloud/api/me'),
          headers: {'Authorization': 'Bearer $token'},
        );

        if (response.statusCode == 200) {
          setState(() {
            user = json.decode(response.body);
            isLoading = false;
          });
        } else {
          _handleError('فشل تحميل بيانات المستخدم');
        }
      } else {
        _handleError('لا يوجد جلسة مستخدم');
      }
    } catch (e) {
      _handleError('خطأ في تحميل البيانات: $e');
    }
  }

  void _handleError(String message) {
    setState(() {
      isLoading = false;
    });

    if (mounted) {
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
  }

  Future<void> _logout() async {
    await storage.delete(key: 'api_token');
    await storage.delete(key: 'pi_user');

    if (mounted) {
      Navigator.pushReplacementNamed(context, '/');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الرئيسية'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : user == null
              ? const Center(child: Text('فشل تحميل بيانات المستخدم'))
              : _buildUserContent(),
    );
  }

  Widget _buildUserContent() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'معلومات المستخدم',
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 20),
          _buildInfoCard('البريد الإلكتروني', user?['email'] ?? 'غير محدد'),
          const SizedBox(height: 10),
          _buildInfoCard('اسم المستخدم', user?['pi_username'] ?? 'غير محدد'),
          const SizedBox(height: 10),
          _buildInfoCard('المفتاح العام', user?['public_key'] != null 
              ? '${user?['public_key'].substring(0, 20)}...' 
              : 'غير محدد'),
          const SizedBox(height: 20),
          ElevatedButton(
            onPressed: _logout,
            child: const Text('تسجيل الخروج'),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoCard(String title, String value) {
    return Card(
      elevation: 4,
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(
              value,
              style: const TextStyle(fontSize: 14),
            ),
          ],
        ),
      ),
    );
  }
}
