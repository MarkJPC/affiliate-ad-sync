import Link from "next/link";

export default function Home() {
  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
          Dashboard
        </h1>
        <p className="mt-2 text-zinc-600 dark:text-zinc-400">
          Affiliate ad management system
        </p>
      </div>

      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <Link
          href="/ads"
          className="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
        >
          <h2 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
            Ads
          </h2>
          <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            View and manage synced ads from all networks
          </p>
        </Link>

        <Link
          href="/advertisers"
          className="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
        >
          <h2 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
            Advertisers
          </h2>
          <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Manage advertiser programs and relationships
          </p>
        </Link>

        <Link
          href="/sites"
          className="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
        >
          <h2 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
            Sites
          </h2>
          <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Configure WordPress sites and placements
          </p>
        </Link>
      </div>

      <div className="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
          Quick Stats
        </h2>
        <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
          Statistics will appear here once ads are synced.
        </p>
      </div>
    </div>
  );
}
