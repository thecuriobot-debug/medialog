<?php
// MediaLog Footer Component - Use on all pages
?>
<footer style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 40px; text-align: center; color: white; margin-top: 60px;">
    <p style="margin-bottom: 15px; font-size: 1.1em; font-weight: 600;">
        📚 MediaLog - Your Letterboxd + Goodreads Tracker
    </p>
    
    <p style="margin-bottom: 20px; font-size: 0.95em; opacity: 0.9;">
        Tracking <?= $totalBooks ?? '782' ?> books from <a href="https://goodreads.com" target="_blank" style="color: white; text-decoration: none; font-weight: 600; border-bottom: 2px solid rgba(255,255,255,0.3); padding-bottom: 2px;">Goodreads</a>
        and <?= $totalMovies ?? '50' ?> movies from <a href="https://letterboxd.com" target="_blank" style="color: white; text-decoration: none; font-weight: 600; border-bottom: 2px solid rgba(255,255,255,0.3); padding-bottom: 2px;">Letterboxd</a>
    </p>
    
    <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px; margin-top: 20px;">
        <p style="font-size: 0.9em; opacity: 0.8; margin-bottom: 10px;">
            Built with ❤️ through Human + AI Collaboration
        </p>
        <p style="font-size: 0.85em; opacity: 0.7;">
            &copy; 2026 <a href="http://1n2.org" style="color: white; text-decoration: none; font-weight: 600;">1n2.org</a> 
            • <a href="http://www.thomashunt.com" style="color: white; text-decoration: none; font-weight: 600;">Thomas Hunt</a>
            • Powered by <a href="https://claude.ai" style="color: white; text-decoration: none; font-weight: 600;">Claude</a>
        </p>
    </div>
</footer>
