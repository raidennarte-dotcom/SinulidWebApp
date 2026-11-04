CREATE DATABASE IF NOT EXISTS sinulid_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sinulid_db;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(30)  NOT NULL,
  email         VARCHAR(190) NOT NULL,
  password      VARCHAR(255) NOT NULL,               -- password_hash() output
  full_name     VARCHAR(100) NULL DEFAULT NULL,
  bio           VARCHAR(300) NULL DEFAULT NULL,
  profile_image VARCHAR(255) NULL DEFAULT NULL,
  anon_name     VARCHAR(30)  NULL DEFAULT NULL,      -- name shown in anonymous mode
  anon_mode     TINYINT(1)   NOT NULL DEFAULT 0,     -- 1 = anonymous mode is on
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_anon_name (anon_name),
  KEY idx_users_full_name (full_name),
  CONSTRAINT chk_users_username  CHECK (username REGEXP '^[A-Za-z0-9_]{3,30}$'),
  CONSTRAINT chk_users_anon_mode CHECK (anon_mode IN (0, 1)),
  CONSTRAINT chk_users_anon_name CHECK (
    anon_name IS NULL
    OR (CHAR_LENGTH(anon_name) BETWEEN 3 AND 30 AND anon_name NOT LIKE '% %')
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  content      TEXT         NOT NULL,               
  image        VARCHAR(255) NULL DEFAULT NULL,
  repost_of    INT UNSIGNED NULL DEFAULT NULL,       
  is_anonymous TINYINT(1)   NOT NULL DEFAULT 0,      
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_posts_user_created (user_id, created_at),  
  KEY idx_posts_created (created_at),               
  KEY idx_posts_repost (repost_of),
  FULLTEXT KEY ft_posts_content (content),           
  CONSTRAINT chk_posts_anonymous CHECK (is_anonymous IN (0, 1)),
  CONSTRAINT fk_posts_user   FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_posts_repost FOREIGN KEY (repost_of) REFERENCES posts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id      INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  parent_id    INT UNSIGNED NULL DEFAULT NULL,       
  content      VARCHAR(500) NOT NULL,
  is_anonymous TINYINT(1)   NOT NULL DEFAULT 0,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_post (post_id, created_at),
  KEY idx_comments_user (user_id),
  KEY idx_comments_parent (parent_id),
  CONSTRAINT chk_comments_anonymous CHECK (is_anonymous IN (0, 1)),
  CONSTRAINT fk_comments_post   FOREIGN KEY (post_id)   REFERENCES posts(id)    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comments_user   FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS likes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_likes_post_user (post_id, user_id),  
  KEY idx_likes_user (user_id),
  CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS follows (
  follower_id INT UNSIGNED NOT NULL,
  followee_id INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, followee_id),           
  KEY idx_follows_followee (followee_id),
  CONSTRAINT fk_follows_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_follows_followee FOREIGN KEY (followee_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,             
  actor_id        INT UNSIGNED NOT NULL,             
  actor_anonymous TINYINT(1)   NOT NULL DEFAULT 0,   
  type            VARCHAR(20)  NOT NULL,
  post_id         INT UNSIGNED NULL DEFAULT NULL,
  is_read         TINYINT(1)   NOT NULL DEFAULT 0,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif_user (user_id, is_read, id),
  CONSTRAINT chk_notif_type      CHECK (type IN ('follow', 'like', 'comment', 'reply', 'share')),
  CONSTRAINT chk_notif_actor_anon CHECK (actor_anonymous IN (0, 1)),
  CONSTRAINT chk_notif_read      CHECK (is_read IN (0, 1)),
  CONSTRAINT fk_notif_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notif_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notif_post  FOREIGN KEY (post_id)  REFERENCES posts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE VIEW v_post_stats AS
SELECT p.id AS post_id,
       (SELECT COUNT(*) FROM likes    l WHERE l.post_id = p.id) AS like_count,
       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
  FROM posts p;

CREATE OR REPLACE VIEW v_public_posts AS
SELECT p.id, p.content, p.image, p.repost_of, p.is_anonymous, p.created_at, p.updated_at,
       CASE WHEN p.is_anonymous = 1 THEN NULL ELSE u.username END AS author_username,
       CASE WHEN p.is_anonymous = 1 THEN u.anon_name
            ELSE COALESCE(NULLIF(u.full_name, ''), u.username) END AS author_name,
       CASE WHEN p.is_anonymous = 1 THEN NULL ELSE u.profile_image END AS author_image,
       s.like_count, s.comment_count
  FROM posts p
  JOIN users u        ON u.id = p.user_id
  JOIN v_post_stats s ON s.post_id = p.id;

CREATE OR REPLACE VIEW v_follower_counts AS
SELECT u.id AS user_id, u.username, COUNT(fu.id) AS visible_followers
  FROM users u
  LEFT JOIN follows f ON f.followee_id = u.id
  LEFT JOIN users fu  ON fu.id = f.follower_id AND fu.anon_mode = 0
 GROUP BY u.id, u.username;

CREATE OR REPLACE VIEW v_unread_notifications AS
SELECT user_id, COUNT(*) AS unread
  FROM notifications
 WHERE is_read = 0
 GROUP BY user_id;

DELIMITER $$

DROP FUNCTION IF EXISTS fn_follower_count $$
CREATE FUNCTION fn_follower_count(p_user INT UNSIGNED, p_viewer INT UNSIGNED)
RETURNS INT UNSIGNED
READS SQL DATA
BEGIN
  DECLARE v_count INT UNSIGNED DEFAULT 0;

  SELECT COUNT(*) INTO v_count
    FROM follows f
    JOIN users u ON u.id = f.follower_id
   WHERE f.followee_id = p_user
     AND (u.anon_mode = 0 OR f.follower_id = p_viewer);

  RETURN v_count;
END $$

DROP PROCEDURE IF EXISTS sp_toggle_like $$
CREATE PROCEDURE sp_toggle_like(IN p_post INT UNSIGNED, IN p_user INT UNSIGNED)
BEGIN
  DECLARE v_liked TINYINT(1) DEFAULT 0;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  DELETE FROM likes WHERE post_id = p_post AND user_id = p_user;

  IF ROW_COUNT() = 0 THEN
    INSERT INTO likes (post_id, user_id) VALUES (p_post, p_user);
    SET v_liked = 1;
  END IF;

  COMMIT;

  SELECT v_liked AS liked,
         (SELECT COUNT(*) FROM likes WHERE post_id = p_post) AS like_count;
END $$

DROP PROCEDURE IF EXISTS sp_toggle_follow $$
CREATE PROCEDURE sp_toggle_follow(IN p_follower INT UNSIGNED, IN p_followee INT UNSIGNED)
BEGIN
  DECLARE v_following TINYINT(1) DEFAULT 0;

  IF EXISTS (SELECT 1 FROM follows WHERE follower_id = p_follower AND followee_id = p_followee) THEN
    DELETE FROM follows WHERE follower_id = p_follower AND followee_id = p_followee;
  ELSE
    INSERT INTO follows (follower_id, followee_id) VALUES (p_follower, p_followee);
    SET v_following = 1;
  END IF;

  SELECT v_following AS following,
         fn_follower_count(p_followee, p_follower) AS follower_count;
END $$

DROP PROCEDURE IF EXISTS sp_set_anon_mode $$
CREATE PROCEDURE sp_set_anon_mode(IN p_user INT UNSIGNED, IN p_on TINYINT)
BEGIN
  IF p_on = 1 AND (SELECT COALESCE(anon_name, '') FROM users WHERE id = p_user) = '' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Create your anonymous name first.';
  END IF;

  UPDATE users SET anon_mode = p_on WHERE id = p_user;
END $$

DROP PROCEDURE IF EXISTS sp_get_feed $$
CREATE PROCEDURE sp_get_feed(IN p_viewer INT UNSIGNED, IN p_limit INT UNSIGNED)
BEGIN
  SELECT p.id,
         CASE WHEN p.is_anonymous = 1 THEN NULL ELSE p.user_id END AS user_id,
         (p.user_id = p_viewer) AS is_mine,
         p.is_anonymous, p.content, p.image, p.repost_of, p.created_at, p.updated_at,
         CASE WHEN p.is_anonymous = 1 THEN NULL ELSE u.username END AS username,
         CASE WHEN p.is_anonymous = 1 THEN u.anon_name ELSE u.full_name END AS full_name,
         CASE WHEN p.is_anonymous = 1 THEN NULL ELSE u.profile_image END AS profile_image,
         s.like_count, s.comment_count,
         EXISTS (SELECT 1 FROM likes l2
                  WHERE l2.post_id = p.id AND l2.user_id = p_viewer) AS liked_by_me
    FROM posts p
    JOIN users u        ON u.id = p.user_id
    JOIN v_post_stats s ON s.post_id = p.id
   ORDER BY p.created_at DESC, p.id DESC
   LIMIT p_limit;
END $$

DROP TRIGGER IF EXISTS trg_users_bi $$
CREATE TRIGGER trg_users_bi BEFORE INSERT ON users
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM users WHERE anon_name = NEW.username) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'That username is already used as an anonymous name.';
  END IF;
END $$

DROP TRIGGER IF EXISTS trg_users_bu $$
CREATE TRIGGER trg_users_bu BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
  IF NEW.username <> OLD.username THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Usernames cannot be changed.';
  END IF;

  IF NEW.anon_name IS NOT NULL AND NEW.anon_name <> ''
     AND (OLD.anon_name IS NULL OR NEW.anon_name <> OLD.anon_name)
     AND EXISTS (SELECT 1 FROM users WHERE username = NEW.anon_name) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Anonymous name cannot be match a real username.';
  END IF;

  IF NEW.anon_mode = 1 AND (NEW.anon_name IS NULL OR NEW.anon_name = '') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Create an anonymous name before turning anonymous mode on.';
  END IF;
END $$

DROP TRIGGER IF EXISTS trg_posts_bi $$
CREATE TRIGGER trg_posts_bi BEFORE INSERT ON posts
FOR EACH ROW
BEGIN
  IF NEW.repost_of IS NULL AND CHAR_LENGTH(TRIM(NEW.content)) = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'A post cannot be empty.';
  END IF;

  IF NEW.repost_of IS NOT NULL
     AND EXISTS (SELECT 1 FROM posts WHERE id = NEW.repost_of AND repost_of IS NOT NULL) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'You can only share an original post.';
  END IF;
END $$


DROP TRIGGER IF EXISTS trg_comments_bi $$
CREATE TRIGGER trg_comments_bi BEFORE INSERT ON comments
FOR EACH ROW
BEGIN
  IF NEW.parent_id IS NOT NULL
     AND NOT EXISTS (SELECT 1 FROM comments
                      WHERE id = NEW.parent_id
                        AND parent_id IS NULL
                        AND post_id = NEW.post_id) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'A reply must belong to a top-level comment on the same post.';
  END IF;
END $$


DROP TRIGGER IF EXISTS trg_follows_bi $$
CREATE TRIGGER trg_follows_bi BEFORE INSERT ON follows
FOR EACH ROW
BEGIN
  IF NEW.follower_id = NEW.followee_id THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'You cannot follow yourself.';
  END IF;

  IF (SELECT anon_mode FROM users WHERE id = NEW.follower_id) = 1 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Turn off anonymous mode to follow people.';
  END IF;
END $$

DELIMITER ;

CREATE EVENT IF NOT EXISTS ev_purge_read_notifications
  ON SCHEDULE EVERY 1 DAY
  DO
    DELETE FROM notifications
     WHERE is_read = 1
       AND created_at < (NOW() - INTERVAL 30 DAY);