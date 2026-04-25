<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品列表</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .header h1 {
            text-align: center;
            font-size: 2.5em;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }

        .product-image::before {
            content: '📦';
            font-size: 60px;
            opacity: 0.8;
        }

        .badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4757;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #222;
            line-height: 1.4;
            min-height: 2.8em;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .stars {
            color: #ffc107;
        }

        .rating-count {
            color: #666;
            font-size: 12px;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .current-price {
            font-size: 24px;
            font-weight: 700;
            color: #ff4757;
        }

        .original-price {
            font-size: 14px;
            color: #999;
            text-decoration: line-through;
        }

        .discount-tag {
            background: #ffe5e5;
            color: #ff4757;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-footer {
            display: flex;
            gap: 10px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cart {
            background: #667eea;
            color: white;
        }

        .btn-cart:hover {
            background: #5568d3;
            transform: translateX(-2px);
        }

        .btn-wishlist {
            background: #f0f0f0;
            color: #666;
            width: auto;
        }

        .btn-wishlist:hover {
            background: #e0e0e0;
            color: #ff4757;
        }

        .footer {
            background: #222;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }

            .header h1 {
                font-size: 1.8em;
            }

            .product-name {
                font-size: 14px;
            }

            .current-price {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛍️ 商品列表</h1>
    </div>

    <div class="container">
        <div class="products-grid">
            @php
            $products = [
                [
                    'name' => '高級商務筆記本',
                    'keyword' => 'notebook',
                    'rating' => '★★★★★',
                    'reviews' => 428,
                    'price' => 699,
                    'original' => 999,
                    'discount' => 30,
                    'badge' => '熱銷'
                ],
                [
                    'name' => '無線藍牙耳機',
                    'keyword' => 'headphones',
                    'rating' => '★★★★☆',
                    'reviews' => 256,
                    'price' => 399,
                    'original' => 599,
                    'discount' => 33,
                    'badge' => '新品'
                ],
                [
                    'name' => '高清攝像頭',
                    'keyword' => 'camera',
                    'rating' => '★★★★★',
                    'reviews' => 512,
                    'price' => 1299,
                    'original' => 1899,
                    'discount' => 32,
                    'badge' => '限時'
                ],
                [
                    'name' => '便攜式行動電源',
                    'keyword' => 'powerbank',
                    'rating' => '★★★★☆',
                    'reviews' => 189,
                    'price' => 129,
                    'original' => 199,
                    'discount' => 35,
                    'badge' => ''
                ],
                [
                    'name' => '人體工學滑鼠',
                    'keyword' => 'mouse',
                    'rating' => '★★★★★',
                    'reviews' => 334,
                    'price' => 89,
                    'original' => 159,
                    'discount' => 44,
                    'badge' => '熱銷'
                ],
                [
                    'name' => '機械鍵盤',
                    'keyword' => 'keyboard',
                    'rating' => '★★★★★',
                    'reviews' => 445,
                    'price' => 549,
                    'original' => 899,
                    'discount' => 39,
                    'badge' => '新品'
                ],
            ];
            @endphp

            @foreach($products as $product)
            <div class="product-card">
                <div class="product-image">
                    @if($product['badge'])
                    <div class="badge">{{ $product['badge'] }}</div>
                    @endif
                </div>
                <div class="product-info">
                    <div class="product-name">{{ $product['name'] }}</div>
                    <div class="product-rating">
                        <span class="stars">{{ $product['rating'] }}</span>
                        <span class="rating-count">({{ $product['reviews'] }} 評價)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">NT${{ number_format($product['price']) }}</span>
                        <span class="original-price">NT${{ number_format($product['original']) }}</span>
                        <span class="discount-tag">-{{ $product['discount'] }}%</span>
                    </div>
                    <div class="product-footer">
                        <button class="btn btn-cart">加入購物車</button>
                        <button class="btn btn-wishlist">❤️</button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2026 電商商城 | 爬蟲抓不到我！ 🛡️</p>
    </div>
</body>
</html>
