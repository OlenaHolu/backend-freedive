@php
  use Illuminate\Support\Carbon;
  $timestamp = Carbon::now()->locale('en')->isoFormat('MMMM D, YYYY · h:mm A');
@endphp

<div style="font-family: Arial, sans-serif; color: #333; padding: 20px; background-color: #f9f9f9;">
  <h2 style="color: #2c3e50; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
    📥 New Contact Message
  </h2>

  <p><strong style="color: #555;">Name:</strong> {{ $name }}</p>
  <p><strong style="color: #555;">Email:</strong> <a href="mailto:{{ $email }}">{{ $email }}</a></p>
  <p><strong style="color: #555;">Date:</strong> {{ $timestamp }}</p>

  <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;" />

  <p style="margin-bottom: 5px;"><strong style="color: #555;">Message:</strong></p>
  <div style="white-space: pre-wrap; background-color: #fff; padding: 15px; border-radius: 6px; border: 1px solid #eee;">
    {!! nl2br(e($bodyMessage)) !!}
  </div>
</div>
