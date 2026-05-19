# Deployment Checklist - Lakshmi Landing

## ✅ Pre-Deployment (COMPLETED)

- [x] Added "Coming Soon" banners to stub pages
- [x] Integrated Formspree for contact form
- [x] Verified no missing images
- [x] Confirmed mobile responsiveness
- [x] Git committed all changes
- [x] Created documentation (FIXES.md, FORMSPREE_SETUP.md)

## 🚀 Deployment Steps

### Step 1: Set Up Formspree (5 minutes)
See `FORMSPREE_SETUP.md` for detailed instructions.

**Quick version:**
1. Go to https://formspree.io and sign up
2. Create new form, set email to: laksmillc@yandex.ru
3. Copy your Form ID (e.g., `xyzabc123`)
4. Edit `index.html` line ~312:
   ```html
   <form id="contact-form" action="https://formspree.io/f/YOUR_FORMSPREE_ID" method="POST">
   ```
   Replace `YOUR_FORMSPREE_ID` with your actual ID
5. Commit change:
   ```bash
   git add index.html
   git commit -m "Add Formspree form ID"
   git push
   ```

### Step 2: Choose Hosting

#### Option A: Netlify (Recommended)
**Why:** Free, automatic HTTPS, easy deploys, CDN

```bash
# Install Netlify CLI
npm install -g netlify-cli

# Deploy
netlify deploy --dir=. --prod
```

**Or via Netlify Dashboard:**
1. Go to https://netlify.com
2. Sign up / Log in
3. Click "Add new site" → "Import an existing project"
4. Connect GitHub repository
5. Settings:
   - Build command: (leave empty)
   - Publish directory: `.` (root)
6. Click "Deploy site"
7. Site goes live at `https://random-name-12345.netlify.app`
8. Optional: Add custom domain

#### Option B: Vercel
```bash
npm install -g vercel
vercel --prod
```

#### Option C: GitHub Pages
```bash
# Push to GitHub
git push origin main

# Enable in repo settings:
# Settings → Pages → Source: main branch → root → Save
# Site will be at: https://username.github.io/lakshmi-landing
```

#### Option D: Traditional Hosting (cPanel, FTP)
1. ZIP the entire project folder
2. Upload to hosting via FTP/cPanel File Manager
3. Extract in public_html or www directory
4. Ensure index.html is in root

### Step 3: Test Production Site

**Critical Tests:**
```
☐ Homepage loads correctly
☐ All navigation links work
☐ Phone number click-to-call works on mobile
☐ WhatsApp button opens WhatsApp
☐ Contact form submits successfully
☐ Receive email at laksmillc@yandex.ru
☐ Success message displays after submission
☐ Form clears after submission
☐ Test on mobile device (not just DevTools)
☐ Test in Safari (iOS users)
☐ Check all service pages load
☐ "Coming Soon" banners visible on stub pages
```

**Browser Testing:**
- Chrome ✓
- Firefox ✓
- Safari (critical for iOS)
- Edge

**Device Testing:**
- iPhone (any model)
- Android phone
- Tablet
- Desktop

### Step 4: Post-Launch Verification

**Formspree:**
1. Submit test form from production site
2. Check Formspree dashboard: https://formspree.io/forms/
3. Verify email received
4. Check spam folder if not in inbox

**Performance:**
- Run Lighthouse audit (Chrome DevTools)
- Target scores: Performance >90, Accessibility >95

**SEO:**
- Submit sitemap to Google Search Console (optional, week 1)
- Verify meta descriptions present
- Test structured data

## 📊 Analytics Setup (Optional - Week 1)

### Google Analytics 4
1. Create GA4 property: https://analytics.google.com
2. Get Measurement ID (G-XXXXXXXXXX)
3. Add to `index.html` before `</head>`:
   ```html
   <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
   <script>
     window.dataLayer = window.dataLayer || [];
     function gtag(){dataLayer.push(arguments);}
     gtag('js', new Date());
     gtag('config', 'G-XXXXXXXXXX');
   </script>
   ```
4. Track events:
   - Form submissions
   - Phone clicks
   - WhatsApp clicks

## 🔧 Troubleshooting

### Form not working
- Check browser console (F12) for errors
- Verify Formspree ID is correct
- Test Formspree status: https://status.formspree.io
- Ensure internet connection stable

### Images not loading
- No images used - all icons are inline SVG
- If adding images later, use relative paths: `images/filename.png`

### Mobile menu not working
- Check js/script.js is loading
- Look for JavaScript errors in console
- Verify Tailwind CSS CDN is accessible

### Site not deploying
- Check build logs in hosting dashboard
- Ensure all files committed to git
- Verify no file permission issues
- Check deployment settings (root directory)

## 🎯 Success Metrics

### Week 1 Goals:
- Site is live and accessible
- Form submissions working
- Receiving 1-2 test submissions
- No critical bugs reported
- Mobile experience smooth

### Month 1 Goals:
- Complete 3-4 service pages
- Add privacy policy
- Set up analytics
- 10+ form submissions
- Consider paid Formspree if needed

## 📞 Support Contacts

**Technical Issues:**
- Formspree: support@formspree.io
- Netlify: https://www.netlify.com/support/
- This project: Check FIXES.md for implementation details

**Domain & Hosting:**
- Check with your hosting provider
- DNS propagation can take 24-48 hours

## 🎉 Go Live!

When everything checks out:
1. ✅ Formspree configured
2. ✅ Site deployed
3. ✅ Form tested
4. ✅ Mobile tested
5. ✅ Email received

**Announce on:**
- Company website (if applicable)
- Social media
- Email signature
- Business cards

---

**Estimated time to go live:** 20-30 minutes  
**Questions?** Review FIXES.md and FORMSPREE_SETUP.md
