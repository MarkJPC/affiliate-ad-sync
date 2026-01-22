import { NextResponse } from "next/server";

// Pattern: routes are thin - they just delegate to controllers
// TODO: Move to controller after schema is finalized

export async function GET() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}
