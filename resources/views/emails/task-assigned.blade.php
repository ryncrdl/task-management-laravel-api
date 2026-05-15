<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>New Task Assigned — TaskFlow</title>
  <!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">

  <!-- Preheader -->
  <div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:#f3f4f6;">
    {{ $task->title }} has been assigned to you by {{ $assigner->name }}.
  </div>

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.10);">

        <!-- Header -->
        <tr><td style="background:#4f46e5;padding:28px 36px;">
          <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td><span style="font-size:20px;font-weight:800;color:#ffffff;letter-spacing:-.3px;">TaskFlow</span></td>
            <td align="right"><span style="font-size:28px;">📋</span></td>
          </tr></table>
          <p style="margin:10px 0 0;font-size:22px;font-weight:700;color:#ffffff;">New Task Assigned</p>
          <p style="margin:4px 0 0;font-size:14px;color:rgba(255,255,255,.8);">You have a new task waiting for you</p>
        </td></tr>

        <!-- Body -->
        <tr><td style="padding:32px 36px;color:#374151;font-size:15px;line-height:1.6;">

          <p style="margin:0 0 20px;">Hi <strong>{{ $assignee->name }}</strong>,</p>
          <p style="margin:0 0 24px;"><strong>{{ $assigner->name }}</strong> has assigned you a new task. Here are the details:</p>

          <!-- Task card -->
          <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:24px;">

            <!-- Title row -->
            <tr><td style="background:#f9fafb;padding:14px 16px;border-bottom:1px solid #e5e7eb;">
              <span style="font-size:16px;font-weight:700;color:#111827;">{{ $task->title }}</span>
            </td></tr>

            @if($task->description)
            <tr><td style="padding:12px 16px;font-size:14px;color:#6b7280;border-bottom:1px solid #e5e7eb;">
              {{ $task->description }}
            </td></tr>
            @endif

            <!-- Info rows -->
            <tr><td style="padding:0;">
              <table width="100%" cellpadding="0" cellspacing="0">

                @if($task->team)
                <tr>
                  <td style="padding:10px 16px;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;font-weight:600;white-space:nowrap;width:110px;">Team</td>
                  <td style="padding:10px 16px;font-size:14px;color:#111827;"><strong>{{ $task->team->name }}</strong></td>
                </tr>
                @endif

                <tr>
                  <td style="padding:10px 16px;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;font-weight:600;white-space:nowrap;width:110px;">Priority</td>
                  <td style="padding:10px 16px;">
                    @php
                      $priorityStyles = [
                        'high'   => 'background:#fee2e2;color:#b91c1c;',
                        'medium' => 'background:#fef9c3;color:#92400e;',
                        'low'    => 'background:#dcfce7;color:#15803d;',
                      ];
                      $ps = $priorityStyles[$task->priority] ?? $priorityStyles['medium'];
                    @endphp
                    <span style="display:inline-block;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:700;{{ $ps }}">{{ ucfirst($task->priority) }}</span>
                  </td>
                </tr>

                @if($task->due_date)
                <tr>
                  <td style="padding:10px 16px;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;font-weight:600;white-space:nowrap;width:110px;">Due Date</td>
                  <td style="padding:10px 16px;font-size:14px;color:#111827;"><strong>{{ $task->due_date->format('D, F j, Y') }}</strong></td>
                </tr>
                @endif

              </table>
            </td></tr>
          </table>

          <!-- CTA -->
          @php $frontendUrl = env('FRONTEND_URL', 'https://task-management-react-e9ni.onrender.com'); @endphp
          <table cellpadding="0" cellspacing="0"><tr><td>
            <a href="{{ $frontendUrl }}/tasks/{{ $task->id }}"
               style="display:inline-block;background:#4f46e5;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;">
              View Task →
            </a>
          </td></tr></table>

        </td></tr>

        <!-- Footer -->
        <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 36px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#9ca3af;">© {{ date('Y') }} TaskFlow · Task Management Platform</p>
          <p style="margin:6px 0 0;font-size:12px;color:#9ca3af;">You received this email because a task was assigned to you. Please do not reply.</p>
        </td></tr>

      </table>
    </td></tr>
  </table>

</body>
</html>
