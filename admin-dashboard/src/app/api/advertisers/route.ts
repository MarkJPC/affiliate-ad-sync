import { listAdvertisers } from "@/lib/controllers/advertisers";

// Pattern: routes are thin - they just delegate to controllers
export const GET = listAdvertisers;

// TODO: Add PATCH handler after controllers are implemented
