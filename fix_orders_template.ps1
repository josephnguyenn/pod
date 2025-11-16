$file = "templates\admin\orders.php"
$content = Get-Content $file -Raw

# Fix meta keys
$content = $content -replace "get_post_meta\(\`$order->ID, '_order_status', true\)", "get_post_status(`$order->ID)"
$content = $content -replace "'_order_total'", "'total_amount'"
$content = $content -replace "'_customer_name'", "'customer_name'"
$content = $content -replace "'_customer_email'", "'customer_email'"

# Fix status values
$content = $content -replace "'apd-pending'", "'apd_pending'"
$content = $content -replace "'apd-confirmed'", "'apd_confirmed'"
$content = $content -replace "'apd-completed'", "'apd_completed'"
$content = $content -replace "'apd-processing'", "'apd_processing'"  
$content = $content -replace "'apd-shipped'", "'apd_shipped'"
$content = $content -replace "'apd-canceled'", "'apd_canceled'"
$content = $content -replace "str_replace\('apd-'", "str_replace('apd_'"

# Save without adding extra newline
$content | Set-Content $file -NoNewline

Write-Host "Fixed $file"
