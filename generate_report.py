import os
import math
from datetime import datetime

# Directory to scan (root of repo, excluding .github)
repo_path = "."
output_dir = ".github"
files_per_part = 25  # Aim for ~25-30 files per part
char_limit = 30000  # Max characters per part
date_str = datetime.now().strftime("%B%d%Y")  # e.g., "April012025"

# Get all files, excluding .github and this script
files = [
    os.path.join(root, f)
    for root, _, filenames in os.walk(repo_path)
    for f in filenames
    if not root.startswith(".github") and f != "generate_report.py"
]

# Sort files for consistency
files.sort()

# Calculate number of parts (minimum 9, or more if needed)
num_parts = max(9, math.ceil(len(files) / files_per_part))
parts = [files[i::num_parts] for i in range(num_parts)]

# Ensure output dir exists
os.makedirs(output_dir, exist_ok=True)

# Generate each part
for i, part_files in enumerate(parts, 1):
    part_content = f"# YardMaster Analysis ReportPart{i}Generated{date_str}\nGenerated: {datetime.now().strftime('%B %d, %Y')}\n\n## Repository Structure\n[Full structure here - truncated for brevity]\n\n## File Contents\n\n"
    char_count = len(part_content)

    for file in part_files:
        try:
            with open(file, "r", encoding="utf-8") as f:
                content = f.read()
                section = f"### File: {file}\n[Fetched from https://raw.githubusercontent.com/mtjoymadman/ai.redlionsalvage.net/main/{file}]\n{content}\n---\n"
                if char_count + len(section) > char_limit:
                    break  # Stop if exceeding char limit
                part_content += section
                char_count += len(section)
        except Exception as e:
            part_content += f"### File: {file}\n[Error: {str(e)}]\n---\n"

    output_file = os.path.join(output_dir, f"YardMaster Analysis ReportPart{i}Generated{date_str}")
    with open(output_file, "w", encoding="utf-8") as f:
        f.write(part_content)

print(f"Generated {num_parts} parts in {output_dir}")
