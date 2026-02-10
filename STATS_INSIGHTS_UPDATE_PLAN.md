# 🔄 Stats & Insights Page Update Plan

## Current Status
- stats.php: 716 lines, needs design update to match
- insights.php: 613 lines, needs design update to match  
- authors.php: ✅ Updated & polished
- directors.php: ✅ Updated & polished

## Design Updates Needed

### Visual Consistency
- [ ] Replace Georgia serif → Sans-serif font stack
- [ ] Update colors: #1a1a1a background → Purple gradient
- [ ] Match navigation style (gold gradient brand)
- [ ] Add stats cards at top
- [ ] Update chart styles
- [ ] Add footer component
- [ ] Mobile responsive improvements

### Stats Page Updates
- [ ] Hero header with purple gradient background
- [ ] Stats grid (4 cards): Total Books, Total Movies, Avg Rating, Total Pages
- [ ] Section headers with icons
- [ ] Chart containers with white cards
- [ ] Professional color scheme
- [ ] Footer component

### Insights Page Updates
- [ ] Hero header matching stats
- [ ] Stats grid: Books This Year, Movies This Year, Reading Pace, Projections
- [ ] Monthly charts in white cards
- [ ] Streak detection highlighted
- [ ] Year-end projection cards
- [ ] Footer component

## Implementation Strategy

Given the file sizes (716 + 613 lines), I'll:
1. Keep all existing functionality/calculations
2. Update only the HTML/CSS portions
3. Match the polished design from authors/directors
4. Test locally before deploying

## Color Scheme (Consistent)
- Background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)
- Books: #1976d2 (blue)
- Movies: #c2185b (pink)
- Accent: #d4af37 (gold)
- Cards: white with shadows
- Text: #1a1a1a (dark)

## Next Steps
1. Update stats.php with new design
2. Update insights.php with new design
3. Test locally
4. Deploy to production
5. Push to GitHub
