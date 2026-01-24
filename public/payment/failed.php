<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فشل الدفع - سوالف</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.6s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .error-icon svg {
            width: 50px;
            height: 50px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        h1 {
            color: #2d3748;
            font-size: 32px;
            margin-bottom: 15px;
        }

        p {
            color: #718096;
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-details {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            color: #c53030;
            font-size: 14px;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">
            <svg viewBox="0 0 52 52">
                <line x1="16" y1="16" x2="36" y2="36"/>
                <line x1="36" y1="16" x2="16" y2="36"/>
            </svg>
        </div>
        
        <h1>❌ فشلت العملية</h1>
        <p>عذراً، لم تتم عملية الدفع بنجاح. يمكنك المحاولة مرة أخرى أو التواصل مع الدعم الفني.</p>

        <?php if(isset($_GET['error'])): ?>
        <div class="error-details">
            <strong>سبب الفشل:</strong> 
            <?php
            $errorMessages = [
                'invalid_data' => 'بيانات غير صحيحة',
                'transaction_not_found' => 'لم يتم العثور على العملية',
                'payment_failed' => 'فشل الدفع',
                'system_error' => 'خطأ في النظام',
            ];
            $error = $_GET['error'];
            echo $errorMessages[$error] ?? 'خطأ غير معروف';
            ?>
        </div>
        <?php endif; ?>

        <div class="buttons">
            <a href="sawalef://payment-failed" class="btn btn-primary">
                العودة للتطبيق
            </a>
            <a href="sawalef://support" class="btn btn-secondary">
                تواصل مع الدعم
            </a>
        </div>
    </div>
</body>
</html>
