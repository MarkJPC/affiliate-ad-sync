-- Seed data for testing

-- Sample sites
INSERT INTO sites (name, url, niche) VALUES
  ('Boating Adventures', 'https://boatingadventures.example.com', 'boating'),
  ('RV Life Blog', 'https://rvlifeblog.example.com', 'rv'),
  ('Camp More Often', 'https://campmoreoften.example.com', 'camping'),
  ('Powersports Weekly', 'https://powersportsweekly.example.com', 'powersports');

-- Sample placements (common banner sizes)
INSERT INTO placements (site_id, name, width, height) VALUES
  (1, 'sidebar_main', 300, 250),
  (1, 'header_leaderboard', 728, 90),
  (1, 'footer_banner', 468, 60),
  (2, 'sidebar_main', 300, 250),
  (2, 'header_leaderboard', 728, 90),
  (3, 'sidebar_main', 300, 250),
  (3, 'mobile_banner', 320, 50),
  (4, 'sidebar_main', 300, 250),
  (4, 'header_leaderboard', 728, 90);

-- Note: Advertisers and ads will be populated by the sync service
