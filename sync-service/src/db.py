"""Database connection and utilities for MySQL."""

import logging
import os
import sys
from contextlib import contextmanager

import pymysql
from dotenv import load_dotenv

load_dotenv()

logger = logging.getLogger(__name__)

# MySQL connection configuration from environment variables
_config = {
    "host": os.getenv("MYSQL_HOST", "localhost"),
    "port": int(os.getenv("MYSQL_PORT", "3306")),
    "user": os.getenv("MYSQL_USER"),
    "password": os.getenv("MYSQL_PASSWORD"),
    "database": os.getenv("MYSQL_DATABASE"),
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor,
    "autocommit": False,
}

# Optional SSL configuration for remote connections
_ssl_ca = os.getenv("MYSQL_SSL_CA")
if _ssl_ca:
    _config["ssl"] = {"ca": _ssl_ca}


def _create_connection():
    """Create a new MySQL connection."""
    return pymysql.connect(**_config)


def test_connection() -> None:
    """Test database connection on startup."""
    try:
        conn = _create_connection()
        with conn.cursor() as cur:
            cur.execute("SELECT NOW()")
            result = cur.fetchone()
            logger.info(f"MySQL connected at: {result['NOW()']}")
        conn.close()
    except Exception as err:
        logger.error(f"MySQL connection failed: {err}")
        sys.exit(1)


@contextmanager
def get_connection():
    """Get a MySQL connection (context manager).

    Usage:
        with get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT * FROM ads")
                results = cur.fetchall()
            conn.commit()
    """
    conn = _create_connection()
    try:
        yield conn
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        conn.close()


def health_check() -> bool:
    """Verify database connection works."""
    try:
        conn = _create_connection()
        with conn.cursor() as cur:
            cur.execute("SELECT 1 AS result")
            result = cur.fetchone()
            conn.close()
            return result is not None and result["result"] == 1
    except Exception:
        return False
