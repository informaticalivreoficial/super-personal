import 'package:super_personal/features/auth/auth_controller.dart';
import 'package:super_personal/features/auth/user.dart';

/// Sessão de aluno já logada, sem tocar na API.
class FakeAuthController extends AuthController {
  @override
  Future<User?> build() async => const User(
        id: 83,
        name: 'Renato Silva',
        email: 'aluno@teste.dev',
        role: 'student',
      );
}

/// Sem sessão (telas de login/splash).
class LoggedOutAuthController extends AuthController {
  @override
  Future<User?> build() async => null;
}

/// Aluno logado com uma troca de e-mail esperando o código de confirmação.
class PendingEmailAuthController extends AuthController {
  @override
  Future<User?> build() async => const User(
        id: 83,
        name: 'Renato Silva',
        email: 'aluno@teste.dev',
        role: 'student',
        pendingEmail: 'novo@teste.dev',
      );
}
