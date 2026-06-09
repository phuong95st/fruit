@extends('layouts.app')

@section('title', 'Liên Hệ Hỗ Trợ | FruitNest')
@section('meta_description', 'Liên hệ với FruitNest qua hotline 0909 123 456 hoặc điền form gửi tin nhắn hỗ trợ trực tiếp. Chúng tôi sẽ phản hồi trong 2 giờ.')

@section('content')
<div class="page active">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Liên hệ</span>
  </div>

  <div class="static-layout">
    <!-- Static Sidebar -->
    <aside class="static-sidebar">
      <div class="static-sidebar-title">Thông tin</div>
      <a class="static-sidebar-link {{ request()->routeIs('page.about') ? 'on' : '' }}" href="{{ route('page.about') }}">Giới thiệu</a>
      <a class="static-sidebar-link {{ request()->routeIs('page.policy') ? 'on' : '' }}" href="{{ route('page.policy') }}">Chính sách</a>
      <a class="static-sidebar-link {{ request()->routeIs('page.contact') ? 'on' : '' }}" href="{{ route('page.contact') }}">Liên hệ</a>
    </aside>

    <!-- Content -->
    <div class="static-content">
      <h2>Liên hệ với chúng tôi</h2>
      <div class="contact-grid">
        <div class="contact-item">
          <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 11.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
          <div class="contact-item-title">Hotline</div>
          <div class="contact-item-val">0909 123 456</div>
          <div class="contact-item-sub">7:00 – 21:00 hàng ngày</div>
        </div>
        <div class="contact-item">
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <div class="contact-item-title">Email</div>
          <div class="contact-item-val">hello@fruitnest.vn</div>
          <div class="contact-item-sub">Phản hồi trong 2 giờ</div>
        </div>
        <div class="contact-item">
          <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <div class="contact-item-title">Địa chỉ</div>
          <div class="contact-item-val">123 Nguyễn Văn Linh</div>
          <div class="contact-item-sub">Quận 7, TP.HCM</div>
        </div>
      </div>
      
      <h3>Gửi tin nhắn cho chúng tôi</h3>
      <form onsubmit="alert('Đã gửi tin nhắn! Chúng tôi sẽ phản hồi sớm.'); return false;">
        <div class="form-row-2">
          <div class="form-group"><label class="form-label">Họ tên</label><input class="form-input" placeholder="Nguyễn Văn A" required/></div>
          <div class="form-group"><label class="form-label">Số điện thoại</label><input class="form-input" placeholder="0909 123 456" required/></div>
        </div>
        <div class="form-group"><label class="form-label">Tiêu đề</label><input class="form-input" placeholder="Tôi cần hỏi về..." required/></div>
        <div class="form-group"><label class="form-label">Nội dung</label><textarea class="form-input" rows="4" placeholder="Nội dung tin nhắn..." required></textarea></div>
        <button type="submit" class="btn btn-primary">Gửi tin nhắn</button>
      </form>
    </div>
  </div>
</div>
</div>
@endsection
