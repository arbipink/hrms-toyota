<!DOCTYPE html>
<html>
<head>
    <title>Leave Request Approval</title>
    <style>
        body { font-family: sans-serif; padding: 40px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #ddd; margin-bottom: 30px; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1a56db; }
        .status-badge { 
            background-color: #dcfce7; color: #166534;
            padding: 5px 10px; border-radius: 4px; font-weight: bold; text-transform: uppercase; font-size: 12px;
        }
        .content { margin-bottom: 30px; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .details-table th { width: 150px; background-color: #f9fafb; }
        .footer { margin-top: 50px; text-align: right; }
        .signature-line { border-top: 1px solid #333; width: 200px; display: inline-block; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Official Leave Permit</h1>
        <p>Document ID: LR-{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $record->user->name }}</strong>,</p>
        <p>This document confirms that your leave request has been reviewed and <span class="status-badge">{{ $record->status }}</span> by the Human Resources Department.</p>

        <table class="details-table">
            <tr>
                <th>Employee Name</th>
                <td>{{ $record->user->name }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>{{ $record->user->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Date Range</th>
                <td>
                    {{ \Carbon\Carbon::parse($record->start_date)->format('d M Y') }} 
                    to 
                    {{ \Carbon\Carbon::parse($record->end_date)->format('d M Y') }}
                </td>
            </tr>
            <tr>
                <th>Duration</th>
                <td>{{ \Carbon\Carbon::parse($record->start_date)->diffInDays(\Carbon\Carbon::parse($record->end_date)) + 1 }} Days</td>
            </tr>
            <tr>
                <th>Reason</th>
                <td>{{ $record->reason }}</td>
            </tr>
            <tr>
                <th>HR Comment</th>
                <td>{{ $record->admin_comment ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Authorized by HR Department</p>
        <p>Date: {{ $record->updated_at->format('d M Y') }}</p>
        <div class="signature-line"></div>
        <p>( Digital Signature )</p>
    </div>
</body>
</html>