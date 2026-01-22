import { listSites } from "@/lib/controllers/sites";

// Pattern: routes are thin - they just delegate to controllers
export const GET = listSites;

// TODO: Add POST handler after controllers are implemented
