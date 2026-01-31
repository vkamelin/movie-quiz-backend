<?php

declare(strict_types=1);

namespace Dotenv {
    class Dotenv
    {
        public static function createImmutable(string $path): self
        {
            return new self();
        }

        public function safeLoad(): void
        {
        }
    }
}

namespace {
    spl_autoload_register(
        static function (string $class): void {
            if (str_starts_with($class, 'App\\')) {
                $path = __DIR__ . '/../../' . str_replace('\\', '/', $class) . '.php';
                if (file_exists($path)) {
                    require $path;
                }
            }
        }
    );

    use App\Console\Kernel;
    use PHPUnit\Framework\TestCase;

    final class WorkerHandlerCommandTest extends TestCase
    {
        public function testFailsWithoutArgument(): void
        {
            $kernel = new Kernel();

            ob_start();
            $exitCode = $kernel->handle(['run', 'worker:handler']);
            $output = ob_get_clean();

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Missing argument', $output);
        }

        public function testProcessesPayload(): void
        {
            $stub = tempnam(sys_get_temp_dir(), 'handler_stub');
            file_put_contents(
                $stub,
                "<?php\n\$GLOBALS['worker_payload'] = json_decode(base64_decode(\$payload, true), true, 512, JSON_THROW_ON_ERROR);\n"
            );

            $_ENV['WORKER_HANDLER_PATH'] = $stub;
            $kernel = new Kernel();

            $update = ['update_id' => 1];
            $payload = base64_encode(json_encode($update, JSON_THROW_ON_ERROR));

            $exitCode = $kernel->handle(['run', 'worker:handler', $payload]);

            $this->assertSame(0, $exitCode);
            $this->assertSame($update, $GLOBALS['worker_payload']);

            unset($_ENV['WORKER_HANDLER_PATH'], $GLOBALS['worker_payload']);
            unlink($stub);
        }
    }
}
