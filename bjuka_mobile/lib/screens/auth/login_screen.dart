import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/providers.dart';
import '../../providers/auth_provider.dart';
import '../../theme/bjuka_brand.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _sliderController = PageController();
  final _formKey = GlobalKey<FormState>();
  Timer? _sliderTimer;
  int _activeSlide = 0;
  bool _isPasswordVisible = false;

  static const _slides = [
    _LoginSlide(
      imageUrl: 'https://picsum.photos/id/180/900/520',
      title: 'Build practical tech skills',
    ),
    _LoginSlide(
      imageUrl: 'https://picsum.photos/id/201/900/520',
      title: 'Track daily internship work',
    ),
    _LoginSlide(
      imageUrl: 'https://picsum.photos/id/48/900/520',
      title: 'Learn through real projects',
    ),
    _LoginSlide(
      imageUrl: 'https://picsum.photos/id/60/900/520',
      title: 'Stay connected with supervisors',
    ),
    _LoginSlide(
      imageUrl: 'https://picsum.photos/id/119/900/520',
      title: 'Grow with B. JUKA Technologies',
    ),
  ];

  @override
  void initState() {
    super.initState();
    _sliderTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (!mounted || !_sliderController.hasClients) {
        return;
      }

      final nextSlide = (_activeSlide + 1) % _slides.length;
      _sliderController.animateToPage(
        nextSlide,
        duration: const Duration(milliseconds: 450),
        curve: Curves.easeOutCubic,
      );
    });
  }

  @override
  void dispose() {
    _sliderTimer?.cancel();
    _sliderController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _login() {
    if (_formKey.currentState!.validate()) {
      final deviceName = Platform.isAndroid ? 'Android Device' : 'iOS Device';
      ref
          .read(authStateProvider.notifier)
          .login(_emailController.text, _passwordController.text, deviceName);
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);

    if (authState.status == AuthStatus.authenticating) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      backgroundColor: BjukaBrand.surface,
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 32, 20, 24),
          children: [
            const SizedBox(height: 36),
            const Center(child: BjukaLogo(width: 280)),
            const SizedBox(height: 36),
            Text(
              'Intern Sign In',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                color: BjukaBrand.ink,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Access attendance, history, and daily internship records.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 24),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18),
                child: Form(
                  key: _formKey,
                  child: Column(
                    children: [
                      TextFormField(
                        controller: _emailController,
                        keyboardType: TextInputType.emailAddress,
                        decoration: const InputDecoration(
                          labelText: 'Email',
                          prefixIcon: Icon(Icons.mail_outline),
                        ),
                        validator: (value) => value == null || value.isEmpty
                            ? 'Please enter email'
                            : null,
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _passwordController,
                        decoration: InputDecoration(
                          labelText: 'Password',
                          prefixIcon: const Icon(Icons.lock_outline),
                          suffixIcon: IconButton(
                            tooltip: _isPasswordVisible
                                ? 'Hide password'
                                : 'Show password',
                            icon: Icon(
                              _isPasswordVisible
                                  ? Icons.visibility_off_outlined
                                  : Icons.visibility_outlined,
                            ),
                            onPressed: () => setState(
                              () => _isPasswordVisible = !_isPasswordVisible,
                            ),
                          ),
                        ),
                        obscureText: !_isPasswordVisible,
                        validator: (value) => value == null || value.isEmpty
                            ? 'Please enter password'
                            : null,
                      ),
                      const SizedBox(height: 18),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: _login,
                          icon: const Icon(Icons.login),
                          label: const Text('Login'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            if (authState.errorMessage != null)
              Padding(
                padding: const EdgeInsets.only(top: 16),
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color: Theme.of(
                      context,
                    ).colorScheme.errorContainer.withValues(alpha: 0.55),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Text(
                      authState.errorMessage!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onErrorContainer,
                      ),
                    ),
                  ),
                ),
              ),
            const SizedBox(height: 22),
            _LoginImageSlider(
              controller: _sliderController,
              slides: _slides,
              activeSlide: _activeSlide,
              onPageChanged: (index) => setState(() => _activeSlide = index),
            ),
          ],
        ),
      ),
    );
  }
}

class _LoginSlide {
  final String imageUrl;
  final String title;

  const _LoginSlide({required this.imageUrl, required this.title});
}

class _LoginImageSlider extends StatelessWidget {
  final PageController controller;
  final List<_LoginSlide> slides;
  final int activeSlide;
  final ValueChanged<int> onPageChanged;

  const _LoginImageSlider({
    required this.controller,
    required this.slides,
    required this.activeSlide,
    required this.onPageChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        AspectRatio(
          aspectRatio: 16 / 9,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: PageView.builder(
              controller: controller,
              onPageChanged: onPageChanged,
              itemCount: slides.length,
              itemBuilder: (context, index) {
                final slide = slides[index];

                return Stack(
                  fit: StackFit.expand,
                  children: [
                    Image.network(
                      slide.imageUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return ColoredBox(
                          color: BjukaBrand.blue.withValues(alpha: 0.1),
                          child: const Center(
                            child: Icon(Icons.image_not_supported_outlined),
                          ),
                        );
                      },
                    ),
                    DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.transparent,
                            BjukaBrand.ink.withValues(alpha: 0.72),
                          ],
                        ),
                      ),
                    ),
                    Positioned(
                      left: 16,
                      right: 16,
                      bottom: 14,
                      child: Text(
                        slide.title,
                        style: Theme.of(context).textTheme.titleMedium
                            ?.copyWith(
                              color: Colors.white,
                              fontWeight: FontWeight.w800,
                            ),
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
        ),
        const SizedBox(height: 10),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(slides.length, (index) {
            final isActive = index == activeSlide;

            return AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              width: isActive ? 18 : 7,
              height: 7,
              margin: const EdgeInsets.symmetric(horizontal: 3),
              decoration: BoxDecoration(
                color: isActive
                    ? BjukaBrand.blue
                    : BjukaBrand.blue.withValues(alpha: 0.25),
                borderRadius: BorderRadius.circular(99),
              ),
            );
          }),
        ),
      ],
    );
  }
}
