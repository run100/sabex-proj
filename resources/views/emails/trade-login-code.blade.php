@php
  $isBind = ($purpose ?? 'login') === 'bind';
  $heading = $isBind ? 'Confirm this email for SABExistCount Trades' : 'Your trade board code';
  $codeDigits = preg_replace('/\s+/', '', (string) $code);
  $codeSpaced = trim(implode(' ', str_split($codeDigits)));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $isBind ? 'Confirm your email' : 'SABExistCount Trades code' }}</title>
</head>
<body style="margin:0;padding:0;background:#0f172a;color:#e2e8f0;font-family:Lato, Helvetica, Arial, sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0f172a;">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#020617;border:1px solid #1e293b;">
          <tr>
            <td style="padding:20px 24px;border-bottom:1px solid #1e293b;">
              <table role="presentation" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="vertical-align:middle;padding-right:12px;">
                    <img src="https://sabexistcount.com/uploads/images/sab/favicon-512.png" width="40" height="40" alt="SABExistCount" style="display:block;border:0;width:40px;height:40px;">
                  </td>
                  <td style="vertical-align:middle;">
                    <p style="margin:0;font-size:16px;font-weight:700;line-height:1.2;color:#f8fafc;">SABExistCount</p>
                    <p style="margin:4px 0 0;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#67e8f9;">Trades</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 24px 8px;">
              <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#67e8f9;">SABExistCount Trades sign-in</p>
              <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;font-weight:700;color:#f8fafc;">{{ $heading }}</h1>
              <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#cbd5e1;">
                This code signs you into <strong style="color:#f8fafc;">SABExistCount Trades</strong>, the Steal a Brainrot trade board.
                It expires in 5 minutes.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:0 24px 24px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0f172a;border:1px solid #164e63;">
                <tr>
                  <td align="center" style="padding:20px 16px;">
                    <p style="margin:0;font-size:32px;line-height:1.2;font-weight:700;color:#ecfeff;font-family:Lato, Helvetica, Arial, sans-serif;">{{ $codeSpaced }}</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:0 24px 28px;">
              <p style="margin:0;font-size:13px;line-height:1.6;color:#94a3b8;">
                Use it once. If you did not request this, ignore the email.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 24px;border-top:1px solid #1e293b;">
              <p style="margin:0;font-size:12px;line-height:1.5;color:#64748b;">SABExistCount Trades · sabexistcount.com</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
