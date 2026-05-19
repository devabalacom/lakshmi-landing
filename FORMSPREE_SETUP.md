# Formspree Setup Instructions

## What is Formspree?

Formspree is a form backend service that lets you collect form submissions via email without writing backend code. Perfect for static websites.

## Setup Steps (5 minutes)

### 1. Create Formspree Account
1. Go to https://formspree.io
2. Click "Get Started" or "Sign Up"
3. Sign up with your email (laksmillc@yandex.ru recommended)
4. Verify your email

### 2. Create a New Form
1. After logging in, click "+ New Form"
2. Form name: "Lakshmi Contact Form"
3. Email to receive submissions: **laksmillc@yandex.ru**
4. Click "Create Form"

### 3. Get Your Form ID
After creating the form, you'll see a form endpoint like:
```
https://formspree.io/f/xyzabc123
```

The part after `/f/` is your **Form ID** (e.g., `xyzabc123`)

### 4. Update index.html
Open `index.html` and find this line (around line 312):
```html
<form id="contact-form" action="https://formspree.io/f/YOUR_FORMSPREE_ID" method="POST" class="bg-white rounded-lg p-8 shadow-xl">
```

Replace `YOUR_FORMSPREE_ID` with your actual Form ID:
```html
<form id="contact-form" action="https://formspree.io/f/xyzabc123" method="POST" class="bg-white rounded-lg p-8 shadow-xl">
```

### 5. Test It!
1. Open your website
2. Fill out the contact form
3. Submit it
4. Check your email (laksmillc@yandex.ru) - you should receive the form submission
5. Also check your Formspree dashboard to see the submission

## Formspree Free Tier Limits

- ✅ 50 submissions per month
- ✅ Email notifications
- ✅ Spam filtering
- ✅ File uploads (up to 10MB)
- ✅ Export submissions as CSV

**Need more?** Upgrade to paid plan ($10/month for 1,000 submissions)

## What Happens When Form is Submitted?

1. User fills out form
2. Formspree receives the data
3. Email sent to laksmillc@yandex.ru with:
   - Name
   - Phone number
   - Message/comment
   - Timestamp
   - User's IP address (for spam prevention)
4. Success message shown to user
5. Form clears automatically

## Email Format You'll Receive

```
Subject: New submission from Lakshmi Contact Form

From: [Name from form]
Phone: [Phone from form]

Message:
[Comment from form]

---
Submitted at: 2026-05-19 15:30 UTC
```

## Troubleshooting

### Form doesn't submit
- Check browser console for errors (F12)
- Verify Form ID is correct in index.html
- Make sure you're using the exact URL from Formspree

### Not receiving emails
- Check spam/junk folder
- Verify email address in Formspree settings
- Check Formspree dashboard - submissions appear there even if email fails

### Error message appears
- Verify you have internet connection
- Check Formspree service status: https://status.formspree.io
- Ensure Form ID is correct

## Alternative: Test Without Formspree

Want to test immediately without Formspree? The form will show a friendly error message and ask users to call instead. This works as a temporary solution.

## Security Notes

- ✅ Formspree has built-in spam protection
- ✅ reCAPTCHA can be enabled in Formspree settings (optional)
- ✅ All submissions are logged in Formspree dashboard
- ✅ Can block specific IP addresses if needed

## Next Steps After Setup

1. Add reCAPTCHA (optional, for extra spam protection)
2. Customize email template in Formspree
3. Set up autoresponder (sends confirmation email to user)
4. Add webhook (if you want to integrate with other tools)

---

**Questions?** Check Formspree documentation: https://help.formspree.io
