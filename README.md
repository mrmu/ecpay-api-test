# ECPay API test

## Installation
* 請複製 config.sample.php 至 config.php，設定正式 PRO 環境的設定值，並切換使用 DEV / PRO 的設定。

## MEMO
* 第一個 Form submit 後會決定 API 每個變動的參數，固定的參數都定義在 config.php 裡
* 第一個 Form submit 後會收集此次 API call 需要的參數，再組成 Mac Value，一併於第二個 Form 傳送給 ECPay
