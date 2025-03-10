<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function processPayment() {
        // Mevcut ödeme işlemleri...
        
        // Ödeme başarılı olduğunda
        if ($paymentSuccessful) {
            // Kredi yükleme işlemini logla
            LogHelper::logSystemActivity(
                'kredi_yukleme', 
                'kredi', 
                'Kullanıcı #' . $userId . ' hesabına ' . $amount . ' kredi yüklendi', 
                [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'package_id' => $packageId,
                    'payment_method' => $paymentMethod,
                    'transaction_id' => $transactionId
                ]
            );
        } else {
            // Başarısız ödemeyi logla
            LogHelper::logSystemActivity(
                'kredi_yukleme_hatasi', 
                'kredi', 
                'Kullanıcı #' . $userId . ' için kredi yükleme başarısız oldu', 
                [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'package_id' => $packageId,
                    'payment_method' => $paymentMethod,
                    'error' => $errorMessage
                ]
            );
        }
    }
} 