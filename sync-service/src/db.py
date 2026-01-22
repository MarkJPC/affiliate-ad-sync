"""Database connection and utilities."""

import logging
import os
import sys

from dotenv import load_dotenv
from psycopg_pool import ConnectionPool

load_dotenv()

logger = logging.getLogger(__name__)

# Build connection string from individual PG_* environment variables
_conninfo = (
    f"host={os.getenv('PG_HOST')} "
    f"port={os.getenv('PG_PORT', '5432')} "
    f"user={os.getenv('PG_USER')} "
    f"password={os.getenv('PG_PASSWORD')} "
    f"dbname={os.getenv('PG_DATABASE')} "
    f"sslmode=require"  # Supabase requires SSL
)

# Connection pool (equivalent to pg.Pool in Node.js)
pool = ConnectionPool(
    conninfo=_conninfo,
    min_size=1,
    max_size=10,  # equivalent to max: 10
    timeout=20,   # connectionTimeoutMillis: 20000
    max_idle=30,  # idleTimeoutMillis: 30000
)


def test_connection() -> None:
    """Test database connection on startup."""
    try:
        with pool.connection() as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT NOW()")
                result = cur.fetchone()
                logger.info(f"PostgreSQL connected at: {result[0]}")
    except Exception as err:
        logger.error(f"PostgreSQL connection failed: {err}")
        sys.exit(1)


def get_connection():
    """Get a connection from the pool."""
    return pool.connection()


def health_check() -> bool:
    """Verify database connection works."""
    try:
        with pool.connection() as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT 1")
                result = cur.fetchone()
                return result is not None and result[0] == 1
    except Exception:
        return False
