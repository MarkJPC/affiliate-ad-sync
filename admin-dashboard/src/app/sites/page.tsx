export default function SitesPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
            Sites
          </h1>
          <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Configure WordPress sites and ad placements
          </p>
        </div>
        <button className="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
          Add Site
        </button>
      </div>

      <div className="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div className="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
          <p className="text-sm text-zinc-600 dark:text-zinc-400">
            Add your WordPress sites to manage ad placements.
          </p>
        </div>
        <div className="p-6">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-zinc-200 dark:border-zinc-800">
                <th className="pb-3 font-medium text-zinc-600 dark:text-zinc-400">
                  Name
                </th>
                <th className="pb-3 font-medium text-zinc-600 dark:text-zinc-400">
                  URL
                </th>
                <th className="pb-3 font-medium text-zinc-600 dark:text-zinc-400">
                  Niche
                </th>
                <th className="pb-3 font-medium text-zinc-600 dark:text-zinc-400">
                  Placements
                </th>
                <th className="pb-3 font-medium text-zinc-600 dark:text-zinc-400">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td
                  colSpan={5}
                  className="py-8 text-center text-zinc-500 dark:text-zinc-500"
                >
                  No sites configured
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
