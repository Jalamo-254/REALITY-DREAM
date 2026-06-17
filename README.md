# Reality Dream Institute — Static Website

This repository contains the public website for Reality Dream Institute. The current goal: provide a fully static, client-side functioning website (no backend required).

**What this repo contains**
- Static pages: index.html, about.html, programs.html, contact.html, enroll.html, gallery.html, blog.html, shop.html
- Assets in `Assets/images/` and `Brochure.pdf` (was `Bronchure.pdf` in the repo).
- `announcement-data.js` supplies homepage announcement cards.

**Changes I made so the site works without a backend**
- `contact.html`: Converted contact form to a client-side `mailto:` flow and added input IDs and a small JS handler. See [contact.html](contact.html).
- `enroll.html`: Replaced embedded Google Form iframe with a static enrollment form that opens the user's mail client (mailto) with enrollment details. See [enroll.html](enroll.html).

**How to run locally (static)**
- Option A — Simple local server (recommended):

```bash
# From the project root
# Python 3
python -m http.server 8000
# then open http://localhost:8000/
```

- Option B — Use XAMPP (already in repo notes): place the folder inside XAMPP's `htdocs` and open `http://localhost/Reality-Dream-Institute-main-main/index.html`.

**How forms work now**
- Both contact and enrollment forms open the user's default mail client with prefilled subject/body (mailto). This avoids any server-side requirement.
- To use a server or Google Forms instead, replace the enrollment iframe or restore a server endpoint.

**Deployment: GitHub Pages (recommended for static sites)**
1. Create a GitHub repo and push the project.
2. In the repo settings > Pages, set the source to the `main` branch (or `gh-pages`) and root directory `/`.
3. Wait a few minutes and your site will be available at `https://<username>.github.io/<repo>`.

Alternative: Deploy static files to Netlify, Vercel, or any static hosting provider.

**Notes & recommendations**
- Brochure file: was `Bronchure.pdf` — updated references to `Brochure.pdf`.
- Images: optimize large images in `Assets/images/` for faster load.
 - I added a Node-based image optimizer script at `scripts/optimize-images.js` that outputs optimized images to `Assets/images/optimized`.
	 A PowerShell runner `scripts/optimize-images.ps1` is provided for Windows.

How to optimize images locally

```powershell
# from project root (Windows PowerShell)
.
scripts\optimize-images.ps1
```

Or manually with Node.js:

```bash
npm install
npm run optimize-images
```
- Accessibility: add `aria-label` and error messages for form validation if you later add a server or client validation.
- Optional: Replace `mailto:` flows with a serverless form (Netlify Forms, Formspree) if you want submissions stored.

**Next suggested steps**
- Optimize images and compress assets.
- Add a minimal `sitemap.xml` and `robots.txt`.
- Optionally add a CI step to build/optimize assets before deployment.

If you want, I can:
- Rename the brochure file and update references.
- Replace `mailto:` forms with an embedded Google Form or serverless form integration.
- Run image optimization and update markup.

---
Generated on 2026-06-15 by the workspace assistant.
