export default function AdvertisersPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
          Advertisers
        </h1>
        <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
          Manage advertiser programs from all affiliate networks
        </p>
      </div>

      <div className="flex gap-4">
        <select className="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
          <option value="">All Networks</option>
          <option value="flexoffers">FlexOffers</option>
          <option value="awin">Awin</option>
          <option value="cj">CJ</option>
          <option value="impact">Impact</option>
        </select>
        <select className="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="paused">Paused</option>
        </select>
      </div>

      <div className="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div className="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
          <p className="text-sm text-zinc-600 dark:text-zinc-400">
            Advertisers will appear here once the sync service runs.
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
                  Network
                </th>
                <th className="pb-3 font-medium text-zinc-600 dark:text-zinc-400">
                  Ads
                </th>
                <th className="pb-3 font-medium text-zinc-600 dark:text-zinc-400">
                  Status
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
                  No advertisers found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
