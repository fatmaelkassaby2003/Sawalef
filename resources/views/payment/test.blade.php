<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار عملية الدفع - Sawalef</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .badge {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .label {
            color: #666;
            font-weight: 500;
        }
        
        .value {
            color: #333;
            font-weight: bold;
        }
        
        .amount {
            font-size: 32px;
            color: #667eea;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #eb3941 0%, #f15e64 100%);
            color: white;
        }
        
        .note {
            background: #fff3cd;
            border: 1px solid #ffd700;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #856404;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .icon {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">💰</div>
        <h1>صفحة الدفع التجريبية</h1>
        <div class="badge">🧪 وضع الاختبار</div>
        
        <div class="info-box">
            <div class="info-row">
                <span class="label">رقم المعاملة:</span>
                <span class="value">{{ $transactionId ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">المبلغ المطلوب:</span>
                <div class="amount">{{ $amount ?? '0' }} جنيه</div>
            </div>
        </div>
        
        <div class="note">
            📢 <strong>ملاحظة:</strong> هذه صفحة اختبار فقط. في الوضع الحقيقي، سيتم توجيهك إلى بوابة الدفع الإلكترونية (Fawaterak) لإتمام العملية.
        </div>
        
        <div class="buttons">
            <button class="btn-success" onclick="simulateSuccess()">
                ✅ تأكيد الدفع (تجريبي)
            </button>
            <button class="btn-danger" onclick="simulateFail()">
                ❌ إلغاء العملية
            </button>
        </div>
    </div>
    
    <script>
        function simulateSuccess() {
            const transactionId = '{{ $transactionId }}';
            const amount = '{{ $amount }}';
            
            // Redirect to WEB callback to update balance
            // Using the route() helper if possible, or relative path
            // We added /payment/test-callback in web.php
            const baseUrl = "{{ url('/') }}";
            window.location.href = `${baseUrl}/payment/test-callback?transaction_id=${transactionId}&status=paid`;
        }
        
        function simulateFail() {
            const transactionId = '{{ $transactionId }}';
            const baseUrl = "{{ url('/') }}";
            
            // Redirect to failed page
            window.location.href = `${baseUrl}/payment/failed.php?transaction_id=${transactionId}&test_mode=true`;
        }
    </script>
</body>
</html>
