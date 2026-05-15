<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Task Assigned</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
    .header { background: #4f46e5; padding: 28px 32px; }
    .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
    .body { padding: 32px; }
    .label { font-size: 12px; text-transform: uppercase; color: #6b7280; letter-spacing: .05em; margin-bottom: 4px; }
    .value { font-size: 16px; color: #111827; margin-bottom: 20px; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
    .badge-high     { background: #fee2e2; color: #b91c1c; }
    .badge-medium   { background: #fef9c3; color: #92400e; }
    .badge-low      { background: #dcfce7; color: #15803d; }
    .btn { display: inline-block; margin-top: 8px; padding: 12px 24px; background: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 15px; }
    .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>📋 TaskFlow — New Task Assigned</h1>
    </div>
    <div class="body">
      <p>Hi <strong>{{ $assignee->name }}</strong>,</p>
      <p><strong>{{ $assigner->name }}</strong> has assigned you a new task.</p>

      <div class="label">Task</div>
      <div class="value">{{ $task->title }}</div>

      @if($task->description)
      <div class="label">Description</div>
      <div class="value">{{ $task->description }}</div>
      @endif

      <div class="label">Priority</div>
      <div class="value">
        <span class="badge badge-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
      </div>

      @if($task->due_date)
      <div class="label">Due Date</div>
      <div class="value">{{ $task->due_date->format('F j, Y') }}</div>
      @endif

      @if(config('app.url'))
      <a class="btn" href="{{ config('app.url') }}/tasks/{{ $task->id }}">View Task →</a>
      @endif
    </div>
    <div class="footer">
      You received this email because you were assigned a task in TaskFlow. Please do not reply to this email.
    </div>
  </div>
</body>
</html>
