--
-- Pre-populate COCO 2017 Dataset with 80 object classes
--

-- Insert COCO 2017 Dataset
INSERT IGNORE INTO AI_Datasets (Id, Name, Description, Version, NumClasses) VALUES
(1, 'COCO', 'Microsoft Common Objects in Context', '2017', 80);

-- Insert all 80 COCO object classes with correct indices (0-79)
INSERT IGNORE INTO AI_Object_Classes (DatasetId, ClassName, ClassIndex, Description) VALUES
(1, 'person', 0, 'Person'),
(1, 'bicycle', 1, 'Bicycle'),
(1, 'car', 2, 'Car'),
(1, 'motorcycle', 3, 'Motorcycle'),
(1, 'airplane', 4, 'Airplane'),
(1, 'bus', 5, 'Bus'),
(1, 'train', 6, 'Train'),
(1, 'truck', 7, 'Truck'),
(1, 'boat', 8, 'Boat'),
(1, 'traffic light', 9, 'Traffic light'),
(1, 'fire hydrant', 10, 'Fire hydrant'),
(1, 'stop sign', 11, 'Stop sign'),
(1, 'parking meter', 12, 'Parking meter'),
(1, 'bench', 13, 'Bench'),
(1, 'bird', 14, 'Bird'),
(1, 'cat', 15, 'Cat'),
(1, 'dog', 16, 'Dog'),
(1, 'horse', 17, 'Horse'),
(1, 'sheep', 18, 'Sheep'),
(1, 'cow', 19, 'Cow'),
(1, 'elephant', 20, 'Elephant'),
(1, 'bear', 21, 'Bear'),
(1, 'zebra', 22, 'Zebra'),
(1, 'giraffe', 23, 'Giraffe'),
(1, 'backpack', 24, 'Backpack'),
(1, 'umbrella', 25, 'Umbrella'),
(1, 'handbag', 26, 'Handbag'),
(1, 'tie', 27, 'Tie'),
(1, 'suitcase', 28, 'Suitcase'),
(1, 'frisbee', 29, 'Frisbee'),
(1, 'skis', 30, 'Skis'),
(1, 'snowboard', 31, 'Snowboard'),
(1, 'sports ball', 32, 'Sports ball'),
(1, 'kite', 33, 'Kite'),
(1, 'baseball bat', 34, 'Baseball bat'),
(1, 'baseball glove', 35, 'Baseball glove'),
(1, 'skateboard', 36, 'Skateboard'),
(1, 'surfboard', 37, 'Surfboard'),
(1, 'tennis racket', 38, 'Tennis racket'),
(1, 'bottle', 39, 'Bottle'),
(1, 'wine glass', 40, 'Wine glass'),
(1, 'cup', 41, 'Cup'),
(1, 'fork', 42, 'Fork'),
(1, 'knife', 43, 'Knife'),
(1, 'spoon', 44, 'Spoon'),
(1, 'bowl', 45, 'Bowl'),
(1, 'banana', 46, 'Banana'),
(1, 'apple', 47, 'Apple'),
(1, 'sandwich', 48, 'Sandwich'),
(1, 'orange', 49, 'Orange'),
(1, 'broccoli', 50, 'Broccoli'),
(1, 'carrot', 51, 'Carrot'),
(1, 'hot dog', 52, 'Hot dog'),
(1, 'pizza', 53, 'Pizza'),
(1, 'donut', 54, 'Donut'),
(1, 'cake', 55, 'Cake'),
(1, 'chair', 56, 'Chair'),
(1, 'couch', 57, 'Couch'),
(1, 'potted plant', 58, 'Potted plant'),
(1, 'bed', 59, 'Bed'),
(1, 'dining table', 60, 'Dining table'),
(1, 'toilet', 61, 'Toilet'),
(1, 'tv', 62, 'TV'),
(1, 'laptop', 63, 'Laptop'),
(1, 'mouse', 64, 'Mouse'),
(1, 'remote', 65, 'Remote'),
(1, 'keyboard', 66, 'Keyboard'),
(1, 'cell phone', 67, 'Cell phone'),
(1, 'microwave', 68, 'Microwave'),
(1, 'oven', 69, 'Oven'),
(1, 'toaster', 70, 'Toaster'),
(1, 'sink', 71, 'Sink'),
(1, 'refrigerator', 72, 'Refrigerator'),
(1, 'book', 73, 'Book'),
(1, 'clock', 74, 'Clock'),
(1, 'vase', 75, 'Vase'),
(1, 'scissors', 76, 'Scissors'),
(1, 'teddy bear', 77, 'Teddy bear'),
(1, 'hair drier', 78, 'Hair drier'),
(1, 'toothbrush', 79, 'Toothbrush');

-- Create default detection settings for common security monitoring objects
-- Only enable person and vehicles by default
INSERT IGNORE INTO AI_Detection_Settings (MonitorId, ObjectClassId, Enabled, ReportDetection, ConfidenceThreshold, BoxColor)
SELECT NULL, Id, 1, 1,
  CASE
    WHEN ClassName = 'person' THEN 60
    ELSE 50
  END,
  CASE
    WHEN ClassName = 'person' THEN '#FF0000'
    WHEN ClassName = 'car' THEN '#0000FF'
    WHEN ClassName = 'truck' THEN '#0066FF'
    WHEN ClassName = 'bus' THEN '#0099FF'
    WHEN ClassName = 'motorcycle' THEN '#00CCFF'
    ELSE '#808080'
  END
FROM AI_Object_Classes
WHERE DatasetId = (SELECT Id FROM AI_Datasets WHERE Name = 'COCO' LIMIT 1)
AND ClassName IN ('person', 'car', 'truck', 'bus', 'motorcycle');
